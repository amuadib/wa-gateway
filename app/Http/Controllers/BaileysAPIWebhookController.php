<?php

namespace App\Http\Controllers;

use App\Events\DeviceDisconnected;
use App\Models\BaileySession;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;

class BaileysAPIWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $data = $request->all();
        $update = [];

        //Sessions
        if ($data['event'] == 'qrcode.updated') {
            $update['qr'] = $data['data']['qr'];
        }
        if ($data['event'] == 'connection.update') {
            $update['event'] = $data['event'];
            if (isset($data['data']['status'])) {
                // $update['status'] = $data['data']['status'];
                $update['status'] = 'Pending';

                //Hapus QR jika sudah connect
                if ($data['data']['status'] == 'connected') {
                    $update['status'] = 'Connected';
                    $update['qr'] = '';
                    $update['disconnected_at'] = null;
                    Cache::forget($data['sessionId']);
                }

                if ($data['data']['status'] == 'disconected') { // Yes, its "disconected"
                    $update['status'] = 'Disconnected';
                    $update['disconnected_at'] = date('Y-m-d H:i:s');
                    event(new DeviceDisconnected(['sessionId' => $data['sessionId']]));
                    TelegramService::sendNotifikasi('[' . date('Y-m-d H:i:s') . '] Error: Device ' . $data['sessionId'] . ' Disconnected');
                }
            }
        }
        if (count($update)) {
            $session = BaileySession::where('id', $data['sessionId']);
            if ($session->exists()) {
                $session->update($update);
            }
        }

        //Messages
        if ($data['event'] == 'messages.update') {
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
