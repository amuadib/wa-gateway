<?php

namespace App\Livewire;

use App\Models\Message;
use App\Services\BaileysService;
use Livewire\Attributes\On;
use Livewire\Component;

class EngineStatusPill extends Component
{
    #[On('engine-status-updated')]
    public function refreshStatus(): void
    {
        // Re-render triggered automatically
    }

    public function render(BaileysService $baileys)
    {
        return view('livewire.engine-status-pill', [
            'health' => $baileys->health(),
            'queued' => Message::where('sent', 0)->count(),
        ]);
    }
}
