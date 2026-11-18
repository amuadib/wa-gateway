<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Kelompok Perangkat</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kelompokkan device dan aplikasi agar pengiriman terdistribusi per unit</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary text-xs">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <span>Tambah Kelompok</span>
        </button>
    </div>

    {{-- Search & Table --}}
    <div class="card overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800">
            <div class="relative max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input wire:model.live.debounce.250ms="search" type="search" placeholder="Cari kelompok..." class="input-field pl-9">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                <thead class="bg-slate-50/70 dark:bg-slate-800/50">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">Nama Kelompok</th>
                        <th class="px-5 py-3">Device</th>
                        <th class="px-5 py-3">Aplikasi</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($groups as $group)
                        <tr wire:key="grp-{{ $group->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3.5 text-xs font-semibold text-slate-900 dark:text-slate-100">{{ $group->nama }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-400 tabular-nums">{{ $group->devices_count }} device</td>
                            <td class="px-5 py-3.5 text-xs text-slate-600 dark:text-slate-400 tabular-nums">{{ $group->tokens_count }} aplikasi</td>
                            <td class="px-5 py-3.5 text-right text-xs whitespace-nowrap">
                                <button wire:click="openEditModal({{ $group->id }})" class="font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-2 py-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition">Edit</button>
                                <button wire:click="delete({{ $group->id }})" wire:confirm="Hapus kelompok {{ $group->nama }}?" class="font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 px-2 py-1 rounded hover:bg-rose-50 dark:hover:bg-rose-500/10 transition">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-xs text-slate-400 dark:text-slate-500">
                                Belum ada kelompok terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: Create / Edit Group --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/70 backdrop-blur-[1px]" x-data @keydown.escape.window="$wire.set('showModal', false)">
            <div class="card w-full max-w-md overflow-hidden shadow-xl" @click.outside="$wire.set('showModal', false)">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $editingId ? 'Edit Kelompok' : 'Tambah Kelompok' }}</h3>
                    <button wire:click="$set('showModal', false)" class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="save" class="p-5 space-y-4">
                    <div>
                        <label class="label-field">Nama Kelompok</label>
                        <input wire:model="nama" type="text" placeholder="Contoh: Akademik" class="input-field" required autofocus>
                        @error('nama') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="$set('showModal', false)" class="btn-secondary text-xs">Batal</button>
                        <button type="submit" class="btn-primary text-xs">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
