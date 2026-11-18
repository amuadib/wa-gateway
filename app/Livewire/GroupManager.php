<?php

namespace App\Livewire;

use App\Models\Group;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kelompok')]
class GroupManager extends Component
{
    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $nama = '';

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->nama = '';
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $group = Group::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $group->id;
        $this->nama = (string) $group->nama;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'nama' => ['required', 'string', 'max:255'],
        ], [], [
            'nama' => 'Nama Kelompok',
        ]);

        if ($this->editingId) {
            Group::findOrFail($this->editingId)->update(['nama' => trim($this->nama)]);
            $this->dispatch('notify', type: 'success', message: 'Kelompok berhasil diperbarui.');
        } else {
            Group::create(['nama' => trim($this->nama)]);
            $this->dispatch('notify', type: 'success', message: 'Kelompok baru berhasil ditambahkan.');
        }

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $group = Group::withCount(['devices', 'tokens'])->findOrFail($id);
        if ($group->devices_count > 0 || $group->tokens_count > 0) {
            $this->dispatch('notify', type: 'error', message: 'Kelompok masih digunakan oleh device atau aplikasi.');
            return;
        }

        $group->delete();
        $this->dispatch('notify', type: 'success', message: 'Kelompok berhasil dihapus.');
    }

    public function render()
    {
        $groups = Group::withCount(['devices', 'tokens'])
            ->when($this->search !== '', fn ($q) => $q->where('nama', 'like', '%' . trim($this->search) . '%'))
            ->orderBy('nama')
            ->get();

        return view('livewire.group-manager', [
            'groups' => $groups,
        ]);
    }
}
