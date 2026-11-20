<?php

namespace App\Livewire;

use App\Models\BaileySession;
use App\Models\Group;
use App\Services\BaileysService;
use App\Services\Pm2Service;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Device WhatsApp')]
class DeviceManager extends Component
{
    public string $groupFilter = '';
    public string $statusFilter = 'all';

    // Create Modal
    public bool $showCreateModal = false;
    public string $deviceId = '';
    public string $groupId = '';

    // QR Zoom Modal
    public ?string $zoomQrId = null;

    protected function allowedGroupIds(): ?array
    {
        $user = auth()->user();
        if ($user->isAdmin()) {
            return null;
        }
        return $user->managedGroupIds();
    }

    public function canManage(BaileySession $device): bool
    {
        $user = auth()->user();
        return $user->isAdmin() || in_array((int) $device->group_id, $user->managedGroupIds(), true);
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->deviceId = '';
        $this->groupId = '';
        $this->showCreateModal = true;
    }

    public function createDevice(BaileysService $baileys): void
    {
        $this->validate([
            'deviceId' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9 _.-]+$/', 'unique:bailey_sessions,id'],
            'groupId' => ['required', 'integer', 'exists:groups,id'],
        ], [
            'deviceId.regex' => 'Nama device hanya boleh huruf, angka, spasi, titik, strip, atau underscore.',
            'deviceId.unique' => 'Nama device sudah digunakan.',
        ], [
            'deviceId' => 'Nama Device',
            'groupId' => 'Kelompok',
        ]);

        $allowed = $this->allowedGroupIds();
        if ($allowed !== null && !in_array((int) $this->groupId, $allowed, true)) {
            $this->addError('groupId', 'Anda tidak memiliki izin untuk kelompok ini.');
            return;
        }

        $res = $baileys->createSession(trim($this->deviceId));

        BaileySession::create([
            'id' => trim($this->deviceId),
            'group_id' => (int) $this->groupId,
            'status' => 'Pending',
        ]);

        $this->showCreateModal = false;

        if (!empty($res['status'])) {
            $this->dispatch('notify', type: 'success', message: "Device {$this->deviceId} dibuat. Tunggu QR muncul untuk scan.");
        } else {
            $this->dispatch('notify', type: 'error', message: $res['message'] ?? 'Device tersimpan di DB, tetapi gagal menghubungi engine Baileys.');
        }
    }

    public function reconnectDevice(string $id, BaileysService $baileys): void
    {
        $device = BaileySession::findOrFail($id);
        if (!$this->canManage($device)) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki izin mengelola device ini.');
            return;
        }

        $device->update(['status' => 'Pending', 'qr' => '']);
        $res = $baileys->reconnectSession($id);

        if (!empty($res['connected'])) {
            $device->update(['status' => 'Connected', 'qr' => '', 'disconnected_at' => null]);
            $this->dispatch('notify', type: 'success', message: "Device {$id} berhasil tersambung kembali.");
        } elseif (!empty($res['status'])) {
            $this->dispatch('notify', type: 'success', message: $res['message'] ?? "Reconnect {$id} dimulai. Cek QR jika diminta.");
        } else {
            $this->dispatch('notify', type: 'error', message: $res['message'] ?? 'Gagal melakukan reconnect.');
        }
    }

    public function syncStatus(string $id, BaileysService $baileys): void
    {
        $device = BaileySession::findOrFail($id);
        $res = $baileys->validateSession($id);

        if (!empty($res['status'])) {
            $newStatus = !empty($res['connected']) ? 'Connected' : 'Disconnected';
            $update = ['status' => $newStatus];
            if ($newStatus === 'Connected') {
                $update['qr'] = '';
                $update['disconnected_at'] = null;
            }
            $device->update($update);
            $this->dispatch('notify', type: 'success', message: "Status {$id}: {$newStatus} ({$res['message']}).");
        } else {
            $this->dispatch('notify', type: 'error', message: $res['message'] ?? 'Gagal memeriksa status device.');
        }
    }

    public function deleteDevice(string $id, BaileysService $baileys): void
    {
        $device = BaileySession::findOrFail($id);
        if (!$this->canManage($device)) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki izin menghapus device ini.');
            return;
        }

        $baileys->deleteSession($id);
        $device->delete();

        if ($this->zoomQrId === $id) {
            $this->zoomQrId = null;
        }

        $this->dispatch('notify', type: 'success', message: "Device {$id} berhasil dihapus.");
    }

    public function startBaileys(Pm2Service $pm2): void
    {
        $res = $pm2->start();
        $this->dispatch('engine-status-updated');
        $this->dispatch('notify', type: $res['ok'] ? 'success' : 'error', message: $res['message']);
    }

    public function restartBaileys(Pm2Service $pm2): void
    {
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
        $allowed = $this->allowedGroupIds();
        $groups = Group::query()
            ->when($allowed !== null, fn ($q) => $q->whereIn('id', $allowed))
            ->orderBy('nama')
            ->get();

        $devices = BaileySession::with('grup')
            ->when($this->groupFilter !== '', fn ($q) => $q->where('group_id', $this->groupFilter))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->orderByRaw("FIELD(status, 'Connected', 'Pending', 'Disconnected')")
            ->orderBy('id')
            ->get();

        $zoomDevice = $this->zoomQrId ? $devices->firstWhere('id', $this->zoomQrId) : null;
        if ($zoomDevice && $zoomDevice->status === 'Connected') {
            $this->zoomQrId = null;
            $zoomDevice = null;
        }

        return view('livewire.device-manager', [
            'devices' => $devices,
            'groups' => $groups,
            'engine' => $baileys->health(),
            'zoomDevice' => $zoomDevice,
        ]);
    }
}
