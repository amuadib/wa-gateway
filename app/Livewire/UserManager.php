<?php

namespace App\Livewire;

use App\Models\Group;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Pengguna')]
class UserManager extends Component
{
    public string $search = '';

    public bool $showModal = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'user';
    public array $managedGroups = [];

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'user';
        $this->managedGroups = [];
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $user = User::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $user->id;
        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->password = '';
        $this->role = (string) ($user->role ?: 'user');
        $this->managedGroups = array_map('strval', $user->managedGroupIds());
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . ($this->editingId ?? 'NULL')],
            'role' => ['required', 'in:admin,user'],
            'managedGroups' => ['array'],
            'password' => $this->editingId ? ['nullable', 'string', 'min:6'] : ['required', 'string', 'min:6'],
        ];

        $this->validate($rules, [], [
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Kata Sandi',
            'role' => 'Peran',
        ]);

        $groupIds = array_values(array_map('intval', $this->managedGroups));

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $payload = [
                'name' => trim($this->name),
                'email' => trim($this->email),
                'role' => $this->role,
                'managed_groups' => $this->role === 'admin' ? [] : $groupIds,
            ];
            if ($this->password !== '') {
                $payload['password'] = $this->password;
            }
            $user->update($payload);
            $this->dispatch('notify', type: 'success', message: 'Data pengguna diperbarui.');
        } else {
            User::create([
                'name' => trim($this->name),
                'email' => trim($this->email),
                'password' => $this->password,
                'role' => $this->role,
                'managed_groups' => $this->role === 'admin' ? [] : $groupIds,
                'email_verified_at' => now(),
            ]);
            $this->dispatch('notify', type: 'success', message: 'Pengguna baru berhasil dibuat.');
        }

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak dapat menghapus akun sendiri.');
            return;
        }

        User::where('id', $id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Pengguna berhasil dihapus.');
    }

    public function render()
    {
        $groups = Group::orderBy('nama')->get();
        $groupMap = $groups->pluck('nama', 'id')->toArray();

        $users = User::query()
            ->when($this->search !== '', function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            })
            ->orderBy('name')
            ->get();

        return view('livewire.user-manager', [
            'users' => $users,
            'groups' => $groups,
            'groupMap' => $groupMap,
        ]);
    }
}
