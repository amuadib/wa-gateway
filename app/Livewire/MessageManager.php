<?php

namespace App\Livewire;

use App\Models\BaileySession;
use App\Models\Message;
use App\Models\Token;
use App\Services\BaileysService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('Pesan')]
class MessageManager extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $statusFilter = 'all';

    #[Url(except: 'all')]
    public string $targetFilter = 'all'; // 'all', 'personal', 'group'

    #[Url(except: '')]
    public string $appFilter = '';

    public int $perPage = 15;
    public array $selected = [];
    public bool $selectPage = false;

    // Create / Edit Modal
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $formTargetType = 'personal'; // 'personal' or 'group'
    public string $formToken = '';
    public string $formNumber = '';
    public string $formName = '';
    public string $formSessionId = '';
    public string $formMessage = '';

    // Group Picker
    public string $formGroupJid = '';
    public array $deviceGroups = [];
    public bool $loadingGroups = false;

    // View Detail Modal
    public bool $showViewModal = false;
    public ?int $viewingId = null;

    protected function getUserDeviceIds(): ?array
    {
        $user = auth()->user();
        if (!$user || $user->isAdmin()) {
            return null;
        }
        return BaileySession::where('user_id', $user->id)->pluck('id')->toArray();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedTargetFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedAppFilter(): void
    {
        $this->resetPage();
        $this->clearSelection();
    }

    public function updatedSelectPage(bool $value): void
    {
        if ($value) {
            $this->selected = $this->baseQuery()
                ->paginate($this->perPage)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function clearSelection(): void
    {
        $this->selected = [];
        $this->selectPage = false;
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->formTargetType = 'personal';
        $this->formToken = Token::value('token') ?? '';
        $this->formNumber = '';
        $this->formName = '';

        $userDeviceIds = $this->getUserDeviceIds();
        $defaultSession = '';
        if ($userDeviceIds !== null) {
            $defaultSession = BaileySession::whereIn('id', $userDeviceIds)->where('status', 'connected')->value('id')
                ?? BaileySession::whereIn('id', $userDeviceIds)->value('id')
                ?? '';
        } else {
            $defaultSession = BaileySession::where('status', 'connected')->value('id') ?? '';
        }

        $this->formSessionId = $defaultSession;
        $this->formMessage = '';
        $this->formGroupJid = '';
        $this->deviceGroups = [];
        $this->loadingGroups = false;
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $userDeviceIds = $this->getUserDeviceIds();
        $msgQuery = Message::query();
        if ($userDeviceIds !== null) {
            $msgQuery->whereIn('sessionId', $userDeviceIds);
        }
        $msg = $msgQuery->findOrFail($id);

        if ((int) $msg->sent !== 0) {
            $this->dispatch('notify', type: 'error', message: 'Hanya pesan dalam antrian yang dapat diedit.');
            return;
        }

        $this->resetValidation();
        $this->editingId = $msg->id;
        $this->formTargetType = $msg->is_group_target ? 'group' : 'personal';
        $this->formToken = (string) $msg->token;
        $this->formNumber = (string) $msg->number;
        $this->formName = (string) ($msg->name ?? '');
        $this->formSessionId = (string) ($msg->sessionId ?? '');
        $this->formMessage = (string) $msg->message;
        $this->formGroupJid = '';
        $this->deviceGroups = [];
        $this->loadingGroups = false;
        $this->showFormModal = true;
    }

    public function setTargetType(string $type): void
    {
        $this->formTargetType = in_array($type, ['personal', 'group'], true) ? $type : 'personal';
        $this->resetValidation('formNumber');
    }

    public function loadDeviceGroups(BaileysService $baileys): void
    {
        $userDeviceIds = $this->getUserDeviceIds();
        $targetSession = $this->formSessionId;

        if (empty($targetSession)) {
            $firstQuery = BaileySession::where('status', 'connected');
            if ($userDeviceIds !== null) {
                $firstQuery->whereIn('id', $userDeviceIds);
            }
            $first = $firstQuery->value('id');
            if ($first) {
                $targetSession = $first;
                $this->formSessionId = $first;
            } else {
                $this->dispatch('notify', type: 'error', message: 'Tidak ada device WhatsApp Anda yang terhubung.');
                return;
            }
        } else {
            if ($userDeviceIds !== null && !in_array($targetSession, $userDeviceIds, true)) {
                $this->dispatch('notify', type: 'error', message: 'Anda tidak memiliki akses ke device ini.');
                return;
            }
        }

        $this->loadingGroups = true;
        $res = $baileys->fetchGroups($targetSession);
        $this->loadingGroups = false;

        if ($res['status'] && !empty($res['groups'])) {
            $this->deviceGroups = $res['groups'];
            $this->dispatch('notify', type: 'success', message: count($this->deviceGroups) . ' grup WhatsApp berhasil dimuat.');
        } else {
            $this->deviceGroups = [];
            $this->dispatch('notify', type: 'error', message: $res['message'] ?? 'Tidak ada grup ditemukan pada device ini.');
        }
    }

    public function updatedFormGroupJid(string $jid): void
    {
        if ($jid === '' || $jid === 'manual') {
            return;
        }

        foreach ($this->deviceGroups as $g) {
            if ($g['id'] === $jid) {
                $this->formNumber = $g['id'];
                $this->formName = $g['subject'];
                break;
            }
        }
    }

    public function saveMessage(): void
    {
        $userDeviceIds = $this->getUserDeviceIds();
        if ($userDeviceIds !== null) {
            if (empty($userDeviceIds)) {
                $this->addError('formSessionId', 'Anda belum memiliki device WhatsApp. Buat device terlebih dahulu di menu Device.');
                return;
            }
            if (!empty($this->formSessionId) && !in_array($this->formSessionId, $userDeviceIds, true)) {
                $this->addError('formSessionId', 'Device yang dipilih bukan milik Anda.');
                return;
            }
            if (empty($this->formSessionId)) {
                $this->formSessionId = BaileySession::whereIn('id', $userDeviceIds)->where('status', 'connected')->value('id')
                    ?? $userDeviceIds[0];
            }
        }

        $isGroup = ($this->formTargetType === 'group');

        if ($isGroup) {
            $this->validate([
                'formToken' => ['required', 'string', 'max:255'],
                'formNumber' => ['required', 'string', 'max:100'],
                'formName' => ['nullable', 'string', 'max:255'],
                'formSessionId' => ['nullable', 'string', 'max:100'],
                'formMessage' => ['required', 'string'],
            ], [], [
                'formToken' => 'Aplikasi',
                'formNumber' => 'ID Grup WhatsApp',
                'formName' => 'Nama Grup',
                'formMessage' => 'Pesan',
            ]);

            $cleanNumber = trim($this->formNumber);
            if (
                $cleanNumber === 'status@broadcast' ||
                str_ends_with($cleanNumber, '@broadcast') ||
                str_ends_with($cleanNumber, '@newsletter')
            ) {
                $this->addError('formNumber', 'Status WA dan Channel tidak didukung sebagai tujuan grup.');
                return;
            }
            if (!str_contains($cleanNumber, '@')) {
                $cleanNumber .= '@g.us';
            }
        } else {
            $this->validate([
                'formToken' => ['required', 'string', 'max:255'],
                'formNumber' => ['required', 'string', 'max:25'],
                'formName' => ['nullable', 'string', 'max:255'],
                'formSessionId' => ['nullable', 'string', 'max:100'],
                'formMessage' => ['required', 'string'],
            ], [], [
                'formToken' => 'Aplikasi',
                'formNumber' => 'Nomor HP',
                'formName' => 'Nama',
                'formMessage' => 'Pesan',
            ]);

            $cleanNumber = $this->sanitizeNumber($this->formNumber);
            if ($cleanNumber === '') {
                $this->addError('formNumber', 'Format nomor HP tidak valid. Masukkan nomor tanpa 0 (contoh: 81234567890) atau dengan awalan +.');
                return;
            }
        }

        $payload = [
            'token' => $this->formToken,
            'number' => $cleanNumber,
            'is_group' => $isGroup,
            'name' => $this->formName !== '' ? $this->formName : null,
            'sessionId' => $this->formSessionId !== '' ? $this->formSessionId : null,
            'message' => $this->formMessage,
        ];

        if ($this->editingId) {
            $msgQuery = Message::query();
            if ($userDeviceIds !== null) {
                $msgQuery->whereIn('sessionId', $userDeviceIds);
            }
            $msg = $msgQuery->findOrFail($this->editingId);

            if ((int) $msg->sent !== 0) {
                $this->dispatch('notify', type: 'error', message: 'Pesan sudah diproses dan tidak bisa diubah.');
                $this->showFormModal = false;
                return;
            }
            $msg->update($payload);
            $this->dispatch('notify', type: 'success', message: 'Pesan antrian berhasil diperbarui.');
        } else {
            Message::create($payload);
            $this->dispatch('notify', type: 'success', message: 'Pesan masuk antrian pengiriman.');
        }

        $this->showFormModal = false;
    }

    public function openViewModal(int $id): void
    {
        $this->viewingId = $id;
        $this->showViewModal = true;
    }

    public function retrySingle(int $id): void
    {
        $userDeviceIds = $this->getUserDeviceIds();
        $query = Message::where('id', $id);
        if ($userDeviceIds !== null) {
            $query->whereIn('sessionId', $userDeviceIds);
        }
        $query->update([
            'sent' => 0,
            'status' => 'PENDING',
            'error' => null,
        ]);
        $this->dispatch('notify', type: 'success', message: 'Pesan dijadwalkan ulang untuk dikirim.');
    }

    public function deleteSingle(int $id): void
    {
        $userDeviceIds = $this->getUserDeviceIds();
        $query = Message::where('id', $id);
        if ($userDeviceIds !== null) {
            $query->whereIn('sessionId', $userDeviceIds);
        }
        $query->delete();
        if ($this->viewingId === $id) {
            $this->showViewModal = false;
            $this->viewingId = null;
        }
        $this->dispatch('notify', type: 'success', message: 'Pesan berhasil dihapus.');
    }

    public function bulkRetry(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $userDeviceIds = $this->getUserDeviceIds();
        $query = Message::whereIn('id', $this->selected);
        if ($userDeviceIds !== null) {
            $query->whereIn('sessionId', $userDeviceIds);
        }

        $count = $query->update([
            'sent' => 0,
            'status' => 'PENDING',
            'error' => null,
        ]);

        $this->clearSelection();
        $this->dispatch('notify', type: 'success', message: "{$count} pesan dijadwalkan ulang untuk dikirim.");
    }

    public function bulkDelete(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $userDeviceIds = $this->getUserDeviceIds();
        $query = Message::whereIn('id', $this->selected);
        if ($userDeviceIds !== null) {
            $query->whereIn('sessionId', $userDeviceIds);
        }

        $count = $query->delete();
        $this->clearSelection();
        $this->dispatch('notify', type: 'success', message: "{$count} pesan berhasil dihapus.");
    }

    public function exportCsv(): StreamedResponse
    {
        $userDeviceIds = $this->getUserDeviceIds();
        $query = !empty($this->selected)
            ? Message::whereIn('id', $this->selected)
                ->when($userDeviceIds !== null, fn ($q) => $q->whereIn('sessionId', $userDeviceIds))
                ->latest('created_at')
            : $this->baseQuery();

        $rows = $query->get(['id', 'sessionId', 'name', 'number', 'is_group', 'sender', 'message', 'status', 'error', 'created_at']);
        $filename = 'pesan-wa-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Device', 'Tipe', 'Nama/Grup', 'Tujuan/JID', 'Pengirim (Grup)', 'Pesan', 'Status', 'Error', 'Waktu']);
            foreach ($rows as $row) {
                $num = (string) $row->number;
                $isGrp = (bool) ($row->is_group || str_ends_with($num, '@g.us'));
                $isLid = str_ends_with($num, '@lid');
                $formattedNum = ($isGrp || $isLid) ? $num : (($num !== '' && $num[0] === '+') ? $num : '+62' . $num);
                $tipeLabel = $isGrp ? 'Grup WA' : ($isLid ? 'Personal (LID)' : 'Personal');
                fputcsv($out, [
                    $row->id,
                    $row->sessionId,
                    $tipeLabel,
                    $row->name,
                    $formattedNum,
                    $row->sender ? '+' . $row->sender : '',
                    $row->message,
                    $row->status,
                    $row->error,
                    $row->created_at,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function sanitizeNumber(string $number): string
    {
        $number = trim($number);
        if ($number === '') {
            return '';
        }

        // WhatsApp status, broadcast, channel exclusion
        if (
            $number === 'status@broadcast' ||
            str_ends_with($number, '@broadcast') ||
            str_ends_with($number, '@newsletter')
        ) {
            return '';
        }

        // WhatsApp group target (must end with @g.us)
        if (str_ends_with($number, '@g.us')) {
            return $number;
        }

        if ($number[0] === '+') {
            return preg_replace('/[^+0-9]/', '', $number);
        }
        $clean = preg_replace('/^62/', '', preg_replace('/^0/', '', preg_replace('/[^0-9]/', '', $number)));
        if ($clean === '' || strlen($clean) > 13 || $clean[0] !== '8') {
            return '';
        }
        return $clean;
    }

    protected function baseQuery()
    {
        $userDeviceIds = $this->getUserDeviceIds();

        return Message::query()
            // Selalu kecualikan status WA, broadcast list, dan channel
            ->where('number', '!=', 'status@broadcast')
            ->where('number', 'not like', '%@broadcast%')
            ->where('number', 'not like', '%@newsletter%')
            ->when($userDeviceIds !== null, fn ($q) => $q->whereIn('sessionId', $userDeviceIds))
            ->when($this->search !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('number', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('sender', 'like', $term)
                        ->orWhere('sessionId', 'like', $term)
                        ->orWhere('message', 'like', $term);
                });
            })
            ->when($this->appFilter !== '', fn ($q) => $q->where('token', $this->appFilter))
            ->when($this->targetFilter !== 'all', function ($q) {
                if ($this->targetFilter === 'group') {
                    $q->where(function ($sub) {
                        $sub->where('is_group', true)
                            ->orWhere('number', 'like', '%@g.us');
                    });
                } else {
                    $q->where(function ($sub) {
                        $sub->where('is_group', false)
                            ->where('number', 'not like', '%@g.us');
                    });
                }
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                match ($this->statusFilter) {
                    'queued' => $q->where('sent', 0),
                    'PENDING' => $q->where('sent', 1)->where('status', 'PENDING'),
                    '3' => $q->where('status', '3'),
                    '4' => $q->where('status', '4'),
                    'received' => $q->where('status', 'RECEIVED'),
                    'FAILED' => $q->where(fn ($s) => $s->where('status', 'FAILED')->orWhere('sent', 99)),
                    default => null,
                };
            })
            ->latest('created_at');
    }

    public function render()
    {
        $userDeviceIds = $this->getUserDeviceIds();

        $tokens = Cache::remember('tokens_map', now()->addMinutes(30), fn () => Token::pluck('name', 'token')->toArray());

        $devicesQuery = BaileySession::orderBy('id');
        if ($userDeviceIds !== null) {
            $devicesQuery->whereIn('id', $userDeviceIds);
        }
        $devices = $devicesQuery->pluck('id')->toArray();

        $viewingQuery = Message::with('app');
        if ($userDeviceIds !== null) {
            $viewingQuery->whereIn('sessionId', $userDeviceIds);
        }
        $viewingMessage = $this->viewingId ? $viewingQuery->find($this->viewingId) : null;

        $queuedQuery = Message::where('sent', 0);
        if ($userDeviceIds !== null) {
            $queuedQuery->whereIn('sessionId', $userDeviceIds);
        }
        $queuedCount = $queuedQuery->count();

        return view('livewire.message-manager', [
            'messages' => $this->baseQuery()->paginate($this->perPage),
            'tokens' => $tokens,
            'devices' => $devices,
            'viewingMessage' => $viewingMessage,
            'queuedCount' => $queuedCount,
        ]);
    }
}
