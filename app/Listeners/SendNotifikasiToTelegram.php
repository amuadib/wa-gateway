<?php

namespace App\Listeners;

use App\Events\DeviceDisconnected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class SendNotifikasiToTelegram
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DeviceDisconnected $event): void
    {
        $sid = $event->sessionId;
        $timeout = 60;
        if (!Cache::has($sid)) {
            Cache::put($sid, time(), now()->addMinute());
        } else {
            if (time() > intval(Cache::get($sid)) + $timeout) {
                \App\Services\TelegramService::sendNotifikasi($sid . ' Disconnected!. Periksa status <a href="' . url('admin/bailey-sessions') . '">disini</a>!');
            }
        }
    }
}
