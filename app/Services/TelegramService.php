<?php
// https://shiroyuki.dev/artikel/2023/03/15/whatsapp-text-formatting-pada-php-part-2/
namespace App\Services;

class TelegramService
{
    public static function sendNotifikasi($message)
    {
        $botApiToken = env('TELEGRAM_API_TOKEN', '000');
        $channelId = '@' . env('TELEGRAM_CHANNEL', 'CHAN');
        $query = http_build_query([
            'chat_id' => $channelId,
            'text' => $message,
        ]);
        $url = "https://api.telegram.org/bot{$botApiToken}/sendMessage?{$query}";
        file_get_contents($url);
    }
}
