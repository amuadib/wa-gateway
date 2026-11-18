<?php

namespace App\Console\Commands;

use App\Models\Message;
use Illuminate\Console\Command;

class DeleteMessage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'message:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus pesan lama';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Message::where('sent', 1)
            ->where('updated_at', '<', date('Y-m-d H:i:s', strtotime(env('KEEP_WA_MESSAGES_DAYS', 7) . ' days ago')))
            ->delete();
    }
}
