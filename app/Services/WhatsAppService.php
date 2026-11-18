<?php
// https://shiroyuki.dev/artikel/2023/03/15/whatsapp-text-formatting-pada-php-part-2/
namespace App\Services;

class WhatsAppService
{
    public static function format_message(string $raw_message): string
    {
        $nl2br_message = nl2br($raw_message);
        $bold = preg_replace('/\*(.*?)\*/', '<b>$1</b>', $nl2br_message);
        $italic = preg_replace('/\_(.*?)\_/', '<i>$1</i>', $bold);
        $strikethrough = preg_replace('/\~(.*?)\~/', '<strike>$1</strike>', $italic);
        $monospace = preg_replace('/\```(.*?)\```/', '<code>$1</code>', $strikethrough);

        return $monospace;
    }
}
