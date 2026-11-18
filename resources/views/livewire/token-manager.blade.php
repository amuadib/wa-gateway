<div class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Aplikasi & Token API</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kelola kredensial aplikasi eksternal yang mengirim pesan melalui gateway</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary text-xs">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            <span>Tambah Aplikasi</span>
        </button>
    </div>

    {{-- Collapsible Quick API Guide (Alpine.js) --}}
    <div x-data="{ open: false }" class="card overflow-hidden">
        <button @click="open = !open" type="button" class="w-full px-5 py-3.5 flex items-center justify-between text-left hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-md bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 text-xs font-mono font-semibold">{ }</span>
                <div>
                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-100">Panduan Singkat Endpoint Kirim Pesan</div>
                    <div class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">POST {{ url('/api/message/send') }}</div>
                </div>
            </div>
            <svg class="h-4 w-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>
        <div x-show="open" x-cloak x-transition class="border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/60 px-5 py-4 text-xs space-y-2.5">
            <p class="text-slate-600 dark:text-slate-400">Kirim request <code class="font-mono bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-1.5 py-0.5 rounded">POST</code> dengan parameter <code class="font-mono">token</code>, <code class="font-mono">number</code>, dan <code class="font-mono">message</code>:</p>
            <pre class="rounded-lg bg-slate-900 dark:bg-black/70 border border-slate-800 text-slate-100 p-3.5 text-[11px] font-mono overflow-x-auto leading-relaxed">curl -X POST "{{ url('/api/message/send') }}" \
  -H "Content-Type: application/json" \
  -d '{"token":"TOKEN_APLIKASI","number":"081234567890","name":"Budi","message":"Halo dari WA-Gateway"}'</pre>
        </div>
    </div>

    {{-- Search + Table --}}
    <div class="card overflow-hidden">
        <div class="p-4 border-b border-slate-100 dark:border-slate-800">
            <div class="relative max-w-sm">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input wire:model.live.debounce.250ms="search" type="search" placeholder="Cari aplikasi atau token..." class="input-field pl-9">
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                <thead class="bg-slate-50/70 dark:bg-slate-800/50">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">Nama Aplikasi</th>
                        <th class="px-5 py-3">Kelompok</th>
                        <th class="px-5 py-3">Token API</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($tokens as $item)
                        <tr wire:key="tok-{{ $item->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition" x-data="{ copied: false, revealed: false }">
                            <td class="px-5 py-3.5 text-xs font-semibold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                {{ $item->name }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-[11px] font-medium text-slate-700 dark:text-slate-300">
                                    {{ $item->grup->nama ?? '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <code class="font-mono text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-950 border border-slate-200/80 dark:border-slate-800 px-2 py-1 rounded">
                                        <span x-show="!revealed">{{ Str::mask($item->token, '•', 6, max(0, strlen($item->token) - 10)) }}</span>
                                        <span x-show="revealed" x-cloak>{{ $item->token }}</span>
                                    </code>
                                    <button @click="revealed = !revealed" type="button" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200" :title="revealed ? 'Sembunyikan' : 'Tampilkan'">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="navigator.clipboard.writeText('{{ $item->token }}'); copied = true; setTimeout(() => copied = false, 1800)"
                                        type="button"
                                        class="px-2 py-1 rounded-lg text-[11px] font-medium transition"
                                        :class="copied ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-800 dark:hover:text-slate-200'"
                                    >
                                        <span x-text="copied ? 'Tersalin' : 'Salin'"></span>
                                    </button>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-right text-xs">
                                <button wire:click="openEditModal({{ $item->id }})" class="font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white px-2 py-1 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition">Edit</button>
                                <button wire:click="delete({{ $item->id }})" wire:confirm="Hapus aplikasi {{ $item->name }}?" class="font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 px-2 py-1 rounded hover:bg-rose-50 dark:hover:bg-rose-500/10 transition">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-10 text-center text-xs text-slate-400 dark:text-slate-500">
                                Belum ada aplikasi terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal: Create / Edit Token --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/70 backdrop-blur-[1px]" x-data @keydown.escape.window="$wire.set('showModal', false)">
            <div class="card w-full max-w-md overflow-hidden shadow-xl" @click.outside="$wire.set('showModal', false)">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ $editingId ? 'Edit Aplikasi' : 'Tambah Aplikasi' }}</h3>
                    <button wire:click="$set('showModal', false)" class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="save" class="p-5 space-y-4">
                    <div>
                        <label class="label-field">Nama Aplikasi</label>
                        <input wire:model="name" type="text" placeholder="Contoh: SIAKAD" class="input-field" required>
                        @error('name') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label-field">Kelompok</label>
                        <select wire:model="groupId" class="input-field" required>
                            <option value="">Pilih kelompok...</option>
                            @foreach($groups as $grp)
                                <option value="{{ $grp->id }}">{{ $grp->nama }}</option>
                            @endforeach
                        </select>
                        @error('groupId') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="label-field">Token API</label>
                        <div class="flex gap-2">
                            <input wire:model="token" type="text" class="input-field font-mono text-xs" required>
                            <button type="button" wire:click="generateToken" class="btn-secondary shrink-0 text-xs px-3" title="Generate UUID baru">
                                Generate
                            </button>
                        </div>
                        @error('token') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
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
