<?php

namespace App\Livewire;

use App\Services\TroubleshootService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Troubleshoot & Diagnostik')]
class Troubleshoot extends Component
{
    public array $overall = [];
    public array $categories = [];
    public array $commands = [];
    public string $activeCategory = 'all';

    public ?string $lastActionName = null;
    public ?string $lastActionOutput = null;
    public bool $showActionModal = false;

    public function boot(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Akses khusus administrator.');
    }

    public function mount(TroubleshootService $service): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Akses khusus administrator.');
        $this->refreshDiagnostics($service);
    }

    public function refreshDiagnostics(TroubleshootService $service): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Akses khusus administrator.');
        $data = $service->checkAll();
        $this->overall = $data['overall'];
        $this->categories = $data['categories'];
        $this->commands = $data['commands'];
    }

    public function executeAction(string $action, TroubleshootService $service): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Akses khusus administrator.');
        $res = match ($action) {
            'start_engine' => $service->startEngine(),
            'restart_engine' => $service->restartEngine(),
            'kill_engine' => $service->killEngine(),
            'run_scheduler_once' => $service->runSchedulerOnce(),
            'run_queue_once' => $service->runQueueOnce(),
            'run_migrations' => $service->runMigrations(),
            'clear_cache' => $service->clearCache(),
            'fix_sessions_dir' => $service->fixSessionsDir(),
            'test_storage_write' => $service->testStorageWrite(),
            'generate_app_key' => $service->generateAppKey(),
            'create_storage_link' => $service->createStorageLink(),
            default => ['ok' => false, 'message' => "Aksi '{$action}' tidak dikenal.", 'output' => null],
        };

        $this->lastActionName = ucwords(str_replace('_', ' ', $action));
        $this->lastActionOutput = $res['output'] ?? $res['message'];

        if ($res['ok']) {
            $this->dispatch('notify', type: 'success', message: $res['message']);
        } else {
            $this->dispatch('notify', type: 'error', message: $res['message']);
            $this->showActionModal = true;
        }

        $this->refreshDiagnostics($service);
    }

    public function closeActionModal(): void
    {
        $this->showActionModal = false;
        $this->lastActionOutput = null;
        $this->lastActionName = null;
    }

    public function setCategory(string $category): void
    {
        $this->activeCategory = $category;
    }

    public function render()
    {
        return view('livewire.troubleshoot');
    }
}
