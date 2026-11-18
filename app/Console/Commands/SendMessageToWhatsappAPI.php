<?php

namespace App\Console\Commands;

use App\Models\BaileySession;
use App\Models\Message;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\RequestException;
use App\Services\TelegramService;

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
    protected $description = 'Kirim pesan ke Whatsapp API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //add random "delay"
        if (env('APP_ENV') != 'local') {
            if (rand(1, 6) % 2 == 0) {
                return;
            }
        }

        $message = Message::where('sent', 0)
            ->first();
        if (!$message) {
            $this->info('Tidak ada antrian pesan');
            return;
        }

        if (env('WHATSAPP_API') == 'baileys') {
            $this->useBaileys($message);
        } elseif (env('WHATSAPP_API') == 'baileys-v7') {
            $this->useBaileysV7($message);
        } else {
            $this->useWhatsappJS($message);
        }
    }

    public function useBaileysV7($message)
    {
        $success = false;
        $country_code = '62';
        if ($message->number[0] == '+') {
            $number = substr($message->number, 1);
        } else {
            $number = $country_code . $message->number;
        }

        $this->line('[' . date('Y-m-d H:i:s') . '] Mengirim pesan ke +' . $number);

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
                        'error' => $error
                    ]);

                    $this->error('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan GAGAL. ' . $error);
                    TelegramService::sendNotifikasi('[' . date('Y-m-d H:i:s') . '] Error: Gagal mengirim pesan ke ' . $number . '. Reason: ' . $error);
                    Log::error('Gagal mengirim pesan ke +' . $number . '. Reason: ' . $error);
                    return;
                }
            } else {
                $sesi = BaileySession::where('status', 'connected');
                if (!$sesi->count()) {
                    $error = 'Tidak ada Device yang terhubung dengan Whatsapp.';
                    $message->update([
                        'sent' => 99, // supaya tidak dipilih lagi untuk dikirimkan
                        'status' => 'FAILED',
                        'error' => $error
                    ]);
                    $this->error('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan GAGAL. ' . $error);
                    TelegramService::sendNotifikasi('[' . date('Y-m-d H:i:s') . '] Error: Gagal mengirim pesan ke ' . $number . '. Reason: ' . $error);
                    Log::error('Gagal mengirim pesan ke +' . $number . '. Reason: ' . $error);
                    return;
                }
                //Jika App punya Sesi/Device yang Aktif, maka gunakan Sesi/Device tsb
                if ($message->app !== null) {
                    $sesi_with_group = BaileySession::where('status', 'connected')
                        ->where('group_id', $message->app->group_id);
                    if ($sesi_with_group->count() > 0) {
                        $sesi = $sesi_with_group;
                    }
                }
            }
            // default gunakan semua device yang tersedia
            $sesi = $sesi->get()
                ->toArray();

            $random = array_rand($sesi);
            $sessionId = $sesi[$random]['id'];
        } else {
            if (!BaileySession::where('id', $message->sessionId)
                ->where('status', 'connected')
                ->exists()) {
                $error = 'Device tidak ditemukan / belum terkoneksi';

                $message->update([
                    'sent' => 99, // supaya tidak dipilih lagi untuk dikirimkan
                    'status' => 'FAILED',
                    'error' => $error
                ]);
                TelegramService::sendNotifikasi('Gagal mengirim pesan ke +' . $number . '. Error: ' . $error);
                Log::error('Gagal mengirim pesan ke +' . $number . '. Reason: ' . $error);
                $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke +' . $number);
                $this->error('Error: ' . $error);
                return;
            }
            //sesi valid
            $sessionId = $message->sessionId;
        }

        try {
            $response = Http::withHeaders([
                'authorization' => 'Bearer ' . env('BAILEYS_V7_API_KEY'),
            ])->withBody(
                json_encode([
                    "id" => $sessionId,
                    "number" => $number,
                    "message" =>  $message->message,
                ]),
                'application/json'
            )
                ->post(
                    env('BAILEYS_V7_API_URL') . '/api/message/send'
                )
                ->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = $e->getMessage();
            $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke +' . $number);
            $this->error('Error: ' . $error);
            TelegramService::sendNotifikasi('Gagal mengirim pesan ke +' . $number . '. Error: ' . $error);
            Log::error('Gagal mengirim pesan ke +' . $number . '. Reason: ' . $error);
            $message->update([
                'sent' => 99, // supaya tidak dipilih lagi untuk dikirimkan
                'status' => 'FAILED',
                'error' => $error
            ]);
            return;
        }

        if (isset($response['status']) && $response['status'] === true) {
            $success = true;
        }

        if (!$success) {
            $message->update([
                'sent' => 99, // supaya tidak dipilih lagi untuk dikirimkan
                'status' => 'FAILED',
                'error' => $response['message']
            ]);
            TelegramService::sendNotifikasi('Gagal mengirim pesan ke +' . $number . '. Error: ' . $response['message']);
            Log::error('Gagal mengirim pesan ke +' . $number . '. Reason: ' . $response['message']);
            $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke +' . $number);
            $this->error('Error: ' . $response['message']);
        } else {
            $message->update([
                'sent' => 1,
                'sessionId' => $sessionId,
                'key' => $response['key']['id'] ?? NULL,
                'status' => 'PENDING',
            ]);
            $this->info('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan Sukses!');
        }
    }
    public function useBaileys($message)
    {
        $suffix = '@s.whatsapp.net';
        $country_code = '62';
        if ($message->number[0] == '+') {
            $number = substr($message->number, 1);
        } else {
            $number = $country_code . $message->number;
        }

        $this->line('[' . date('Y-m-d H:i:s') . '] Mengirim pesan ke +' . $number);

        if ($message->sessionId == null) {
            $sesi = BaileySession::where('status', 'connected');
            if (!$sesi->count()) {
                $this->error('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan GAGAL. Tidak ada Device yang terhubung dengan Whatsapp.');
                TelegramService::sendNotifikasi('[' . date('Y-m-d H:i:s') . '] Error: Gagal mengirim pesan ke ' . $number . '. Reason: Tidak ada Device yang terhubung dengan Whatsapp');
                return;
            }

            //Jika App punya Sesi/Device yang Aktif, maka gunakan Sesi/Device tsb
            if ($message->app !== null) {
                $sesi_with_group = BaileySession::where('status', 'connected')
                    ->where('group_id', $message->app->group_id);
                if ($sesi_with_group->count() > 0) {
                    $sesi = $sesi_with_group;
                }
            }

            // default gunakan semua device yang tersedia
            $sesi = $sesi->get()
                ->toArray();

            $random = array_rand($sesi);
            $sessionId = $sesi[$random]['id'];
        } else {
            if (!BaileySession::where('id', $message->sessionId)
                ->where('status', 'connected')
                ->exists()) {
                $error = 'Device tidak ditemukan / belum terkoneksi';
                TelegramService::sendNotifikasi('Gagal mengirim pesan ke +' . $number . '. Error: ' . $error);
                $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke +' . $number);
                $this->error('Error: ' . $error);

                $message->update([
                    'sent' => 99, // supaya tidak dipilih lagi untuk dikirimkan
                    'status' => 'FAILED',
                    'error' => $error
                ]);
                return;
            }
            //sesi valid
            $sessionId = $message->sessionId;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => env('BAILEYS_API_KEY'),
            ])->withBody(
                json_encode([
                    "jid" => $number . $suffix,
                    "type" => "number",
                    "message" => [
                        "text" => $message->message
                    ],
                ]),
                'application/json'
            )
                ->post(
                    env('BAILEYS_API_URL') . '/' . $sessionId . '/messages/send'
                )
                ->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $error = $e->getMessage();
            $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke +' . $number);
            $this->error('Error: ' . $error);
            TelegramService::sendNotifikasi('Gagal mengirim pesan ke +' . $number . '. Error: ' . $error);
            $message->update([
                'sent' => 99, // supaya tidak dipilih lagi untuk dikirimkan
                'status' => 'FAILED',
                'error' => $error
            ]);
            return;
        }

        if (isset($response['error'])) {
            $message->update([
                'sent' => 99, // supaya tidak dipilih lagi untuk dikirimkan
                'status' => 'FAILED',
                'error' => $response['error']
            ]);
            TelegramService::sendNotifikasi('Gagal mengirim pesan ke +' . $number . '. Error: ' . $response['error']);
            $this->error('[' . date('Y-m-d H:i:s') . '] Gagal mengirim pesan ke +' . $number);
            $this->error('Error: ' . $response['error']);
        } else {
            $message->update([
                'sent' => 1,
                'sessionId' => $sessionId,
                'key' => $response['key']['id'],
                'status' => 'PENDING',
            ]);
            $this->info('[' . date('Y-m-d H:i:s') . '] Pengiriman pesan Sukses!');
        }
    }

    public function useWhatsappJS($message)
    {
        $client = new \GuzzleHttp\Client([
            'base_uri' => env('WA_API_URI'),
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);

        try {
            $response = $client->post('/send', ['form_params' => [
                'phone' => $message->number,
                'message' => $message->message,
            ]]);
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $this->error('Gangguan koneksi ke Whatsapp API');
        }

        $response_json = $response->getBody()->getContents();
        $response_arr = json_decode($response_json, true);

        if ($response_arr == null) {
            Log::error('Gagal mengirim pesan ke ' . $message->number);
            if (isset($response_arr['data']['meta'])) {
                Log::error($response_arr['data']['meta']);
            }
            $this->warn('Tidak ada respon dari Whatsapp API');
        } else {
            if ($response_arr['error'] === false) {
                $message->update(['sent' => 1]);
                $this->info('Sukses mengirim pesan ke ' . $message->number);
            } else {
                Log::error('Terjadi kesalahan. Silahkan periksa halaman ' . env('WA_API_URI'));
                $this->error('Terjadi kesalahan pada Whatsapp. Silahkan hubungi Admin');
                if (isset($response_arr['data']['meta'])) {
                    Log::error($response_arr['data']['meta']);
                }
            }
        }
    }
}
