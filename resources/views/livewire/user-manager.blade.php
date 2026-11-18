<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Manajemen Pengguna</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Atur hak akses administrator dan operator kelompok device</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary text-xs">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <span>Tambah Pengguna</span>
        </button>
    </div>

    {{-- Table Card --}}
    <div class="card overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800">
            <div class="relative max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input wire:model.live.debounce.250ms="search" type="search" placeholder="Cari nama atau email..." class="input-field pl-9">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                <thead class="bg-slate-50/70 dark:bg-slate-800/50">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">Pengguna</th>
                        <th class="px-5 py-3">Peran</th>
                        <th class="px-5 py-3">Kelompok Dikelola</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($users as $u)
                        <tr wire:key="usr-{{ $u->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="text-xs font-semibold text-slate-900 dark:text-slate-100">{{ $u->name }}</div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $u->email }}</div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset
                                    {{ $u->isAdmin() ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 ring-emerald-200 dark:ring-emerald-500/20' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 ring-slate-200 dark:ring-slate-700' }}">
                                    {{ $u->isAdmin() ? 'Admin' : 'Operator' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                @if($u->isAdmin())
                                    <span class="text-xs text-slate-400 dark:text-slate-500">Semua kelompok</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($u->managedGroupIds() as $gid)
                                            <span class="inline-flex items-center rounded-md bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-300">
                                                {{ $groupMap[$gid] ?? '#' . $gid }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400 dark:text-slate-500">—</span>
                                        @endforelse
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right text-xs whitespace-nowrap">
                                <button wire:click="openEditModal({{ $u->id }})" class="font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-2 py-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition">Edit</button>
                                @if($u->id !== auth()->id())
                                    <button wire:click="delete({{ $u->id }})" wire:confirm="Hapus pengguna {{ $u->name }}?" class="font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 px-2 py-1 rounded hover:bg-rose-50 dark:hover:bg-rose-500/10 transition">Hapus</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-xs text-slate-400 dark:text-slate-500">
                                Belum ada pengguna.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: Create / Edit User --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/70 backdrop-blur-[1px]" x-data @keydown.escape.window="$wire.set('showModal', false)">
            <div class="card w-full max-w-md overflow-hidden shadow-xl" @click.outside="$wire.set('showModal', false)">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $editingId ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h3>
                    <button wire:click="$set('showModal', false)" class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="save" class="p-5 space-y-4">
                    <div>
                        <label class="label-field">Nama</label>
                        <input wire:model="name" type="text" class="input-field" required>
                        @error('name') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label-field">Email</label>
                        <input wire:model="email" type="email" class="input-field" required>
                        @error('email') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label-field">Kata Sandi {{ $editingId ? '(kosongkan jika tidak diubah)' : '' }}</label>
                        <input wire:model="password" type="password" class="input-field" {{ $editingId ? '' : 'required' }}>
                        @error('password') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label-field">Peran</label>
                        <select wire:model.live="role" class="input-field">
                            <option value="user">Operator (terbatas per kelompok)</option>
                            <option value="admin">Administrator (akses penuh)</option>
                        </select>
                    </div>

                    @if($role === 'user')
                        <div>
                            <label class="label-field">Kelompok yang Dikelola</label>
                            <div class="max-h-36 overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-700/80 p-2.5 space-y-2 bg-slate-50/50 dark:bg-slate-950/60">
                                @forelse($groups as $grp)
                                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                        <input type="checkbox" wire:model="managedGroups" value="{{ $grp->id }}" class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-emerald-600 focus:ring-emerald-500/30">
                                        <span>{{ $grp->nama }}</span>
                                    </label>
                                @empty
                                    <p class="text-xs text-slate-400 dark:text-slate-500">Belum ada kelompok tersedia.</p>
                                @endforelse
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="$set('showModal', false)" class="btn-secondary text-xs">Batal</button>
                        <button type="submit" class="btn-primary text-xs">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
