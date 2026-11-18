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

        //Messages (delivery/read status updates & incoming messages)
        if (in_array($event, ['messages.update', 'messages.upsert'], true) && isset($data['data']['messages']['id'])) {
            Message::where('key', $data['data']['messages']['id'])
                ->update([
                    'status' => $data['data']['messages']['status'],
                    'error' => ''
                ]);
        }
        if (env('DEBUG_BAILEYS_WEBHOOK')) {
            Log::info($data);
        }
        return response()->json(['success' => true]);
    }
}
