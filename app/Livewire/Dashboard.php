<?php

namespace App\Livewire;

use App\Models\BaileySession;
use App\Models\Group;
use App\Models\Message;
use App\Models\Token;
use App\Services\BaileysService;
use App\Services\Pm2Service;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function testWebhook(BaileysService $baileys): void
    {
        $res = $baileys->testWebhook();
        $ok = (bool) ($res['success'] ?? false);
        $this->dispatch('notify', type: $ok ? 'success' : 'error', message: $ok
            ? 'Test webhook berhasil dikirim ke Laravel.'
            : ($res['message'] ?? 'Gagal mengirim test webhook.'));
    }

    public function startBaileys(Pm2Service $pm2): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $res = $pm2->start();
        $this->dispatch('engine-status-updated');
        $this->dispatch('notify', type: $res['ok'] ? 'success' : 'error', message: $res['message']);
    }

    public function restartBaileys(Pm2Service $pm2): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $res = $pm2->restart();
        $this->dispatch('engine-status-updated');
        $this->dispatch('notify', type: $res['ok'] ? 'success' : 'error', message: $res['message']);
    }

    public function killBaileys(Pm2Service $pm2): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $res = $pm2->kill();
        $this->dispatch('engine-status-updated');
        $this->dispatch('notify', type: $res['ok'] ? 'success' : 'error', message: $res['message']);
    }

    public function render(BaileysService $baileys)
    {
        $counts = DB::table('messages')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN sent = 0 THEN 1 ELSE 0 END) as queued,
                SUM(CASE WHEN status = 'PENDING' AND sent = 1 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = '3' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status = '4' THEN 1 ELSE 0 END) as `read`,
                SUM(CASE WHEN status = 'FAILED' OR sent = 99 THEN 1 ELSE 0 END) as failed
            ")
            ->first();

        $stats = [
            'total' => (int) ($counts->total ?? 0),
            'queued' => (int) ($counts->queued ?? 0),
            'pending' => (int) ($counts->pending ?? 0),
            'delivered' => (int) ($counts->delivered ?? 0),
            'read' => (int) ($counts->read ?? 0),
            'failed' => (int) ($counts->failed ?? 0),
        ];

        $devices = BaileySession::with('grup')->orderByRaw("FIELD(status, 'Connected', 'Pending', 'Disconnected')")->get();
        $connectedDevices = $devices->where('status', 'Connected')->count();

        $tokenNames = Cache::remember('tokens_map', now()->addMinutes(30), fn () => Token::pluck('name', 'token')->toArray());

        $recentMessages = Message::query()
            ->latest('created_at')
            ->limit(8)
            ->get();

        $engine = $baileys->health();

        return view('livewire.dashboard', [
            'stats' => $stats,
            'devices' => $devices,
            'connectedDevices' => $connectedDevices,
            'totalDevices' => $devices->count(),
            'totalApps' => count($tokenNames),
            'totalGroups' => Group::count(),
            'tokenNames' => $tokenNames,
            'recentMessages' => $recentMessages,
            'engine' => $engine,
        ]);
    }
}
