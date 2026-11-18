<?php

namespace App\Livewire;

use App\Models\BaileySession;
use App\Models\Message;
use App\Models\Token;
use Illuminate\Support\Facades\Cache;
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

    #[Url(except: '')]
    public string $appFilter = '';

    public int $perPage = 15;
    public array $selected = [];
    public bool $selectPage = false;

    // Create / Edit Modal
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $formToken = '';
    public string $formNumber = '';
    public string $formName = '';
    public string $formSessionId = '';
    public string $formMessage = '';

    // View Detail Modal
    public bool $showViewModal = false;
    public ?int $viewingId = null;

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
        $this->formToken = Token::value('token') ?? '';
        $this->formNumber = '';
        $this->formName = '';
        $this->formSessionId = '';
        $this->formMessage = '';
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $msg = Message::findOrFail($id);
        if ((int) $msg->sent !== 0) {
            $this->dispatch('notify', type: 'error', message: 'Hanya pesan dalam antrian yang dapat diedit.');
            return;
        }

        $this->resetValidation();
        $this->editingId = $msg->id;
        $this->formToken = (string) $msg->token;
        $this->formNumber = (string) $msg->number;
        $this->formName = (string) ($msg->name ?? '');
        $this->formSessionId = (string) ($msg->sessionId ?? '');
        $this->formMessage = (string) $msg->message;
        $this->showFormModal = true;
    }

    public function saveMessage(): void
    {
        $this->validate([
            'formToken' => ['required', 'string', 'max:255'],
            'formNumber' => ['required', 'string', 'max:20'],
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

        $payload = [
            'token' => $this->formToken,
            'number' => $cleanNumber,
            'name' => $this->formName !== '' ? $this->formName : null,
            'sessionId' => $this->formSessionId !== '' ? $this->formSessionId : null,
            'message' => $this->formMessage,
        ];

        if ($this->editingId) {
            $msg = Message::findOrFail($this->editingId);
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
        Message::where('id', $id)->update([
            'sent' => 0,
            'status' => 'PENDING',
            'error' => null,
        ]);
        $this->dispatch('notify', type: 'success', message: 'Pesan dijadwalkan ulang untuk dikirim.');
    }

    public function deleteSingle(int $id): void
    {
        Message::where('id', $id)->delete();
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

        $count = Message::whereIn('id', $this->selected)->update([
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

        $count = Message::whereIn('id', $this->selected)->delete();
        $this->clearSelection();
        $this->dispatch('notify', type: 'success', message: "{$count} pesan berhasil dihapus.");
    }

    public function exportCsv(): StreamedResponse
    {
        $query = !empty($this->selected)
            ? Message::whereIn('id', $this->selected)->latest('created_at')
            : $this->baseQuery();

        $rows = $query->get(['id', 'sessionId', 'name', 'number', 'message', 'status', 'error', 'created_at']);
        $filename = 'pesan-wa-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Device', 'Nama', 'Nomor', 'Pesan', 'Status', 'Error', 'Waktu']);
            foreach ($rows as $row) {
                $num = (string) $row->number;
                $formattedNum = ($num !== '' && $num[0] === '+') ? $num : '+62' . $num;
                fputcsv($out, [
                    $row->id,
                    $row->sessionId,
                    $row->name,
                    $formattedNum,
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
        return Message::query()
            ->when($this->search !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('number', 'like', $term)
                        ->orWhere('name', 'like', $term)
                        ->orWhere('sessionId', 'like', $term)
                        ->orWhere('message', 'like', $term);
                });
            })
            ->when($this->appFilter !== '', fn ($q) => $q->where('token', $this->appFilter))
            ->when($this->statusFilter !== 'all', function ($q) {
                match ($this->statusFilter) {
                    'queued' => $q->where('sent', 0),
                    'PENDING' => $q->where('sent', 1)->where('status', 'PENDING'),
                    '3' => $q->where('status', '3'),
                    '4' => $q->where('status', '4'),
                    'FAILED' => $q->where(fn ($s) => $s->where('status', 'FAILED')->orWhere('sent', 99)),
                    default => null,
                };
            })
            ->latest('created_at');
    }

    public function render()
    {
        $tokens = Cache::remember('tokens_map', now()->addMinutes(30), fn () => Token::pluck('name', 'token')->toArray());
        $devices = BaileySession::orderBy('id')->pluck('id')->toArray();
        $viewingMessage = $this->viewingId ? Message::with('app')->find($this->viewingId) : null;

        return view('livewire.message-manager', [
            'messages' => $this->baseQuery()->paginate($this->perPage),
            'tokens' => $tokens,
            'devices' => $devices,
            'viewingMessage' => $viewingMessage,
            'queuedCount' => Message::where('sent', 0)->count(),
        ]);
    }
}
