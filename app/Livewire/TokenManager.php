<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\Token;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Aplikasi & Token')]
class TokenManager extends Component
{
    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $token = '';
    public string $groupId = '';

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->name = '';
        $this->token = (string) Str::uuid();
        $this->groupId = '';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $record = Token::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $record->id;
        $this->name = (string) $record->name;
        $this->token = (string) $record->token;
        $this->groupId = (string) ($record->group_id ?? '');
        $this->showModal = true;
    }

    public function generateToken(): void
    {
        $this->token = (string) Str::uuid();
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:255', 'unique:tokens,token,' . ($this->editingId ?? 'NULL')],
            'groupId' => ['required', 'integer', 'exists:groups,id'],
        ], [], [
            'name' => 'Nama Aplikasi',
            'token' => 'Token API',
            'groupId' => 'Kelompok',
        ]);

        $data = [
            'name' => trim($this->name),
            'token' => trim($this->token),
            'group_id' => (int) $this->groupId,
        ];

        if ($this->editingId) {
            Token::findOrFail($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Aplikasi berhasil diperbarui.');
        } else {
            Token::create($data);
            $this->dispatch('notify', type: 'success', message: 'Aplikasi baru berhasil ditambahkan.');
        }

        Cache::forget('tokens_map');
        Cache::forget('valid_token');
        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        Token::where('id', $id)->delete();
        Cache::forget('tokens_map');
        Cache::forget('valid_token');
        $this->dispatch('notify', type: 'success', message: 'Aplikasi berhasil dihapus.');
    }

    public function render()
    {
        $tokens = Token::with('grup')
            ->when($this->search !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where('name', 'like', $term)->orWhere('token', 'like', $term);
            })
            ->orderBy('name')
            ->get();

        return view('livewire.token-manager', [
            'tokens' => $tokens,
            'groups' => Group::orderBy('nama')->get(),
        ]);
    }
}
