<?php

namespace App\Console\Commands;

use App\Models\BaileySession;
use App\Models\Message;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendMessageToWhatsappAPI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:send-to-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim antrian pesan ke Baileys v7 WhatsApp API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Illuminate\Support\Facades\Cache::put('message_queue:last_run', now()->toIso8601String(), 86400);

        // Add random "delay" in non-local environments
        if (env('APP_ENV') != 'local') {
            if (rand(1, 6) % 2 == 0) {
                return;
            }
        }

        $message = Message::where('sent', 0)->first();
        if (!$message) {
            $this->info('Tidak ada antrian pesan');
            return;
        }

        $this->useBaileysV7($message);
    }

    public function useBaileysV7($message)
    {
        $success = false;
        $rawNumber = trim((string) $message->number);

        // Abaikan dan gagalkan jika target adalah WhatsApp Status, Broadcast list, atau Newsletter
        if (
            $rawNumber === '' ||
            $rawNumber === 'status@broadcast' ||
            str_ends_with($rawNumber, '@broadcast') ||
            str_ends_with($rawNumber, '@newsletter')
        ) {
            $error = 'Target WhatsApp Status / Broadcast / Newsletter tidak didukung untuk pengiriman pesan.';
            $message->update([
                'sent' => 99,
                'status' => 'FAILED',
                'error' => $error,
            ]);
            $this->error('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan dibatalkan: ' . $error);
            return;
        }

        $isGroup = (bool) ($message->is_group ?? false) || str_ends_with($rawNumber, '@g.us');

        if ($isGroup) {
            $number = $rawNumber;
            $targetLabel = '[Grup ' . ($message->name ?: $number) . ']';
        } else {
            if ($rawNumber !== '' && $rawNumber[0] === '+') {
                $number = substr($rawNumber, 1);
            } elseif (str_starts_with($rawNumber, '62')) {
                $number = $rawNumber;
            } else {
                $number = '62' . ltrim($rawNumber, '0');
            }
            $targetLabel = '+' . $number;
        }

        $this->line('[' . date('Y-m-d H:i:s') . '] Mengirim pesan ke ' . $targetLabel);

        if ($message->sessionId == null) {
            if (env('SEND_ONLY_FROM_DEVICE_GROUP', true)) {
                $sesi_with_group = BaileySession::where('status', 'connected')
                    ->where('group_id', $message->app->group_id);
                if ($sesi_with_group->count() > 0) {
                    $sesi = $sesi_with_group;
                } else {
                    $error = 'Tidak ada Device yang terhubung dari [' . $message->app->name . ']';

                    $message->update([
                        'sent' => 99, // supaya tidak dipilih lagi untuk dikirimkan
                        'status' => 'FAILED',
                        'error' => $error,
                    ]);

                    $this->error('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan GAGAL. ' . $error);
                    TelegramService::sendNotifikasi('[' . date('Y-m-d H:i:s') . '] Error: Gagal mengirim pesan ke ' . $targetLabel . '. Reason: ' . $error);
                    Log::error('Gagal mengirim pesan ke ' . $targetLabel . '. Reason: ' . $error);
                    return;
                }
            } else {
                $sesi = BaileySession::where('status', 'connected');
                if (!$sesi->count()) {
                    $error = 'Tidak ada Device yang terhubung dengan Whatsapp.';
                    $message->update([
                        'sent' => 99,
                        'status' => 'FAILED',
                        'error' => $error,
                    ]);
                    $this->error('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan GAGAL. ' . $error);
                    TelegramService::sendNotifikasi('[' . date('Y-m-d H:i:s') . '] Error: Gagal mengirim pesan ke ' . $targetLabel . '. Reason: ' . $error);
                    Log::error('Gagal mengirim pesan ke ' . $targetLabel . '. Reason: ' . $error);
                    return;
                }
                // Jika App punya Sesi/Device yang Aktif, maka gunakan Sesi/Device tsb
                if ($message->app !== null) {
                    $sesi_with_group = BaileySession::where('status', 'connected')
                        ->where('group_id', $message->app->group_id);
                    if ($sesi_with_group->count() > 0) {
                        $sesi = $sesi_with_group;
                    }
                }
            }

            // Default gunakan semua device yang tersedia
            $sesi = $sesi->get()->toArray();
            $random = array_rand($sesi);
            $sessionId = $sesi[$random]['id'];
        } else {
            if (!BaileySession::where('id', $message->sessionId)
                ->where('status', 'connected')
                ->exists()) {
                $error = 'Device tidak ditemukan / belum terkoneksi';

                $message->update([
                    'sent' => 99,
                    'status' => 'FAILED',
                    'error' => $error,
                ]);
                TelegramService::sendNotifikasi('Gagal mengirim pesan ke ' . $targetLabel . '. Error: ' . $error);
                Log::error('Gagal mengirim pesan ke ' . $targetLabel . '. Reason: ' . $error);
                $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke ' . $targetLabel);
                $this->error('Error: ' . $error);
                return;
            }
            $sessionId = $message->sessionId;
        }

        try {
            $response = Http::withHeaders([
                'authorization' => 'Bearer ' . env('BAILEYS_V7_API_KEY'),
            ])->withBody(
                json_encode([
                    'id' => $sessionId,
                    'number' => $number,
                    'message' => $message->message,
                ]),
                'application/json'
            )
                ->post(
                    rtrim((string) env('BAILEYS_V7_API_URL'), '/') . '/api/message/send'
                )
                ->json();
        } catch (ConnectionException $e) {
            $error = $e->getMessage();
            $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke ' . $targetLabel);
            $this->error('Error: ' . $error);
            TelegramService::sendNotifikasi('Gagal mengirim pesan ke ' . $targetLabel . '. Error: ' . $error);
            Log::error('Gagal mengirim pesan ke ' . $targetLabel . '. Reason: ' . $error);
            $message->update([
                'sent' => 99,
                'status' => 'FAILED',
                'error' => $error,
            ]);
            return;
        }

        if (isset($response['status']) && $response['status'] === true) {
            $success = true;
        }

        if (!$success) {
            $errMsg = $response['message'] ?? 'Unknown error';
            $message->update([
                'sent' => 99,
                'status' => 'FAILED',
                'error' => $errMsg,
            ]);
            TelegramService::sendNotifikasi('Gagal mengirim pesan ke ' . $targetLabel . '. Error: ' . $errMsg);
            Log::error('Gagal mengirim pesan ke ' . $targetLabel . '. Reason: ' . $errMsg);
            $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke ' . $targetLabel);
            $this->error('Error: ' . $errMsg);
        } else {
            $message->update([
                'sent' => 1,
                'sessionId' => $sessionId,
                'key' => $response['key']['id'] ?? null,
                'status' => 'PENDING',
            ]);
            $this->info('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan Sukses!');
        }
    }
}
