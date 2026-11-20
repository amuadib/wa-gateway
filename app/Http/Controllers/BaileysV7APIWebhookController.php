<?php

namespace App\Http\Controllers;

use App\Events\DeviceDisconnected;
use App\Models\BaileySession;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;

class BaileysV7APIWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        $event = $data['event'] ?? null;
        if (!$event) {
            return response()->json(['success' => false, 'message' => 'Missing event'], 400);
        }
        if ($event == 'webhook.test') {
            Log::info($data);
            return response()->json(['success' => true]);
        }
        $update = [];

        //Sessions
        if ($event == 'qrcode.updated') {
            $update['qr'] = $data['data']['qr'] ?? '';
        }
        if ($event == 'connection.update') {
            $update['event'] = $event;
            if (isset($data['data']['status'])) {
                $update['status'] = 'Pending';

                //Hapus QR jika sudah connect
                if ($data['data']['status'] == 'connected') {
                    $update['status'] = 'Connected';
                    $update['qr'] = '';
                    $update['disconnected_at'] = null;
                    Cache::forget($data['sessionId']);
                }

                if ($data['data']['status'] == 'disconnected') {
                    $update['status'] = 'Disconnected';
                    $update['qr'] = '';
                    $update['disconnected_at'] = date('Y-m-d H:i:s');
                    event(new DeviceDisconnected(['sessionId' => $data['sessionId']]));
                    TelegramService::sendNotifikasi('[' . date('Y-m-d H:i:s') . '] Error: Device ' . $data['sessionId'] . ' Disconnected');
                }
            }
        }
        if (count($update) && !empty($data['sessionId'])) {
            BaileySession::updateOrCreate(
                ['id' => $data['sessionId']],
                $update
            );
        }

        // Messages (delivery/read status updates & incoming messages)
        if (in_array($event, ['messages.update', 'messages.upsert'], true) && isset($data['data']['messages']['id'])) {
            $msgData = $data['data']['messages'];
            $remoteJid = trim((string) ($msgData['from'] ?? ''));

            // Abaikan WhatsApp Status, broadcast, dan channel / newsletter
            if (
                $remoteJid === '' ||
                $remoteJid === 'status@broadcast' ||
                str_ends_with($remoteJid, '@broadcast') ||
                str_ends_with($remoteJid, '@newsletter')
            ) {
                return response()->json(['success' => true, 'ignored' => 'status_broadcast']);
            }

            $msgKey = $msgData['id'];
            $existing = Message::where('key', $msgKey)->first();

            if ($existing) {
                $existing->update([
                    'status' => $msgData['status'] ?? $existing->status,
                    'error' => '',
                ]);
            } elseif ($event === 'messages.upsert' && !empty($msgData['text'])) {
                // Incoming message (Personal or Group)
                $isGroup = (bool) ($msgData['isGroup'] ?? false) || str_ends_with($remoteJid, '@g.us');
                $sessionId = $data['sessionId'] ?? null;
                $session = $sessionId ? BaileySession::find($sessionId) : null;
                $groupId = $session?->group_id;

                $resolvedToken = null;
                $resolvedName = $msgData['senderName'] ?? null;

                // 1. Cek apakah pesan membalas (quote) pesan yang pernah dikirim/diterima sebelumnya
                $quotedId = $msgData['quotedId'] ?? null;
                if ($quotedId) {
                    $quotedMsg = Message::where('key', $quotedId)->first();
                    if ($quotedMsg && !empty($quotedMsg->token)) {
                        $resolvedToken = $quotedMsg->token;
                        if (empty($resolvedName) && !empty($quotedMsg->name)) {
                            $resolvedName = $quotedMsg->name;
                        }
                    }
                }

                // 2. Cek riwayat percakapan dengan nomor/kontak ini pada device yang sama
                if (!$resolvedToken && $sessionId) {
                    $cleanSender = trim((string) ($msgData['sender'] ?? ''));
                    $phoneNumber = trim((string) ($msgData['phoneNumber'] ?? ''));

                    $searchNumbers = array_values(array_filter([
                        $phoneNumber !== '' ? $phoneNumber : null,
                        $cleanSender !== '' ? $cleanSender : null,
                        $remoteJid !== '' ? $remoteJid : null,
                    ]));

                    $coreDigits = null;
                    foreach ([$phoneNumber, $cleanSender, $remoteJid] as $cand) {
                        if ($cand !== '' && !str_ends_with($cand, '@lid') && !str_ends_with($cand, '@g.us')) {
                            $digits = preg_replace('/[^0-9]/', '', $cand);
                            $cd = preg_replace('/^62/', '', ltrim($digits, '0'));
                            if (strlen($cd) >= 8) {
                                $coreDigits = $cd;
                                break;
                            }
                        }
                    }

                    $prevQuery = Message::where('sessionId', $sessionId);
                    if ($coreDigits || !empty($searchNumbers)) {
                        $prevQuery->where(function ($q) use ($searchNumbers, $coreDigits) {
                            if (!empty($searchNumbers)) {
                                $q->whereIn('number', $searchNumbers);
                            }
                            if ($coreDigits) {
                                $q->orWhere('number', 'like', "%{$coreDigits}%");
                            }
                        });
                    } else {
                        $prevQuery->where('number', $remoteJid);
                    }

                    $prevMsg = $prevQuery->latest('id')->first();
                    if ($prevMsg && !empty($prevMsg->token)) {
                        $resolvedToken = $prevMsg->token;
                        if (empty($resolvedName) && !empty($prevMsg->name)) {
                            $resolvedName = $prevMsg->name;
                        }
                    }
                }

                // 3. Cari Token yang berada di dalam KELOMPOK (group_id) yang sama dengan Device ini
                if (!$resolvedToken && $groupId) {
                    // 3a. Cek token yang paling baru/sering dipakai pada device ini yang valid di kelompoknya
                    $lastDeviceToken = Message::where('sessionId', $sessionId)
                        ->whereNotNull('token')
                        ->latest('id')
                        ->value('token');

                    if ($lastDeviceToken && \App\Models\Token::where('token', $lastDeviceToken)->where('group_id', $groupId)->exists()) {
                        $resolvedToken = $lastDeviceToken;
                    } else {
                        // 3b. Ambil token pertama yang terdaftar pada kelompok device ini
                        $resolvedToken = \App\Models\Token::where('group_id', $groupId)->value('token');
                    }
                }

                // 4. Fallback Terakhir: jika device belum ditentukan kelompoknya
                if (!$resolvedToken) {
                    $resolvedToken = \App\Models\Token::value('token') ?? 'INBOX';
                }

                Message::create([
                    'token' => $resolvedToken,
                    'sessionId' => $sessionId,
                    'number' => $remoteJid,
                    'is_group' => $isGroup,
                    'sender' => $msgData['sender'] ?? null,
                    'name' => $resolvedName,
                    'message' => $msgData['text'] ?? '',
                    'status' => 'RECEIVED',
                    'sent' => 1,
                    'key' => $msgKey,
                    'error' => null,
                ]);
            }
        }
        if (env('DEBUG_BAILEYS_WEBHOOK')) {
            Log::info($data);
        }
        return response()->json(['success' => true]);
    }
}
