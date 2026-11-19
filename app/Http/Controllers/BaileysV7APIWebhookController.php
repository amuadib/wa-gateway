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
            $msgKey = $msgData['id'];
            $existing = Message::where('key', $msgKey)->first();

            if ($existing) {
                $existing->update([
                    'status' => $msgData['status'] ?? $existing->status,
                    'error' => '',
                ]);
            } elseif ($event === 'messages.upsert' && !empty($msgData['text'])) {
                // Incoming message (Personal or Group)
                $remoteJid = (string) ($msgData['from'] ?? '');
                $isGroup = (bool) ($msgData['isGroup'] ?? str_ends_with($remoteJid, '@g.us'));
                $defaultToken = \App\Models\Token::value('token') ?? 'INBOX';

                Message::create([
                    'token' => $defaultToken,
                    'sessionId' => $data['sessionId'] ?? null,
                    'number' => $remoteJid,
                    'is_group' => $isGroup,
                    'sender' => $msgData['sender'] ?? null,
                    'name' => $msgData['senderName'] ?? null,
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
