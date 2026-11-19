<div wire:poll.15s class="space-y-5">
    {{-- Header Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Antrian & Riwayat Pesan</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pantau status pengiriman WhatsApp Personal & Grup secara real-time
                @if($queuedCount > 0)
                    &middot; <span class="font-medium text-amber-700 dark:text-amber-400">{{ number_format($queuedCount) }} menunggu dikirim</span>
                @endif
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="exportCsv" class="btn-secondary text-xs">
                <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                </svg>
                <span>Ekspor CSV</span>
            </button>
            <button wire:click="openCreateModal" class="btn-primary text-xs">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Kirim Pesan</span>
            </button>
        </div>
    </div>

    {{-- Filters Card --}}
    <div class="card p-4 space-y-3.5">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Cari nomor HP, ID grup, pengirim, device, atau isi pesan..."
                    class="input-field pl-9"
                >
            </div>
            <div class="w-full md:w-56">
                <select wire:model.live="appFilter" class="input-field">
                    <option value="">Semua Aplikasi</option>
                    @foreach($tokens as $tokValue => $tokName)
                        <option value="{{ $tokValue }}">{{ $tokName }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Filter Tabs (Target & Status) --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 pt-1 border-t border-slate-100 dark:border-slate-800">
            {{-- Target Type Tabs --}}
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500 mr-1">Tipe:</span>
                <button
                    wire:click="$set('targetFilter', 'all')"
                    class="px-2.5 py-1 rounded-full text-xs font-medium transition {{ $targetFilter === 'all' ? 'bg-slate-900 dark:bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700' }}"
                >
                    Semua
                </button>
                <button
                    wire:click="$set('targetFilter', 'personal')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium transition {{ $targetFilter === 'personal' ? 'bg-sky-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700' }}"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                    <span>Personal</span>
                </button>
                <button
                    wire:click="$set('targetFilter', 'group')"
                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium transition {{ $targetFilter === 'group' ? 'bg-purple-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700' }}"
                >
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                    <span>Grup WA</span>
                </button>
            </div>

            {{-- Status Filter Tabs --}}
            @php
                $statusTabs = [
                    'all' => 'Semua Status',
                    'queued' => 'Antrian',
                    'PENDING' => 'Pending',
                    '3' => 'Terkirim',
                    '4' => 'Dibaca',
                    'received' => 'Diterima',
                    'FAILED' => 'Gagal',
                ];
            @endphp
            <div class="flex flex-wrap items-center gap-1.5">
                @foreach($statusTabs as $key => $label)
                    <button
                        wire:click="$set('statusFilter', '{{ $key }}')"
                        class="px-2.5 py-1 rounded-full text-xs font-medium transition {{ $statusFilter === $key ? 'bg-slate-900 dark:bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Bulk Action Bar --}}
    @if(count($selected) > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 dark:border-emerald-500/30 bg-emerald-50/80 dark:bg-emerald-500/10 px-4 py-2.5 text-xs">
            <span class="font-medium text-emerald-900 dark:text-emerald-200">
                <strong class="tabular-nums">{{ count($selected) }}</strong> pesan dipilih
            </span>
            <div class="flex items-center gap-2">
                <button wire:click="bulkRetry" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-500 px-3 py-1.5 font-medium text-white hover:bg-amber-600 transition">
                    Kirim Ulang
                </button>
                <button wire:click="exportCsv" class="btn-secondary text-xs py-1.5 px-3">
                    Ekspor Terpilih
                </button>
                <button wire:click="bulkDelete" wire:confirm="Hapus {{ count($selected) }} pesan terpilih?" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 font-medium text-white hover:bg-rose-700 transition">
                    Hapus
                </button>
                <button wire:click="clearSelection" class="text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 px-2 py-1">Batal</button>
            </div>
        </div>
    @endif

    {{-- Table Card --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                <thead class="bg-slate-50/70 dark:bg-slate-800/50">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="pl-4 pr-2 py-3 w-10">
                            <input type="checkbox" wire:model.live="selectPage" class="rounded border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-emerald-600 focus:ring-emerald-500/30">
                        </th>
                        <th class="px-3 py-3">Aplikasi / Device</th>
                        <th class="px-3 py-3">Target / Penerima</th>
                        <th class="px-3 py-3">Pesan</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Waktu</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($messages as $m)
                        @php
                            $num = (string) $m->number;
                            $isGrp = (bool) ($m->is_group || str_contains($num, '@g.us') || str_contains($num, '-'));
                            $displayNum = $isGrp ? $num : (($num !== '' && $num[0] === '+') ? $num : '+62' . $num);
                        @endphp
                        <tr wire:key="msg-{{ $m->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="pl-4 pr-2 py-3 align-top">
                                <input type="checkbox" wire:model.live="selected" value="{{ $m->id }}" class="mt-0.5 rounded border-slate-300 dark:border-slate-700 dark:bg-slate-900 text-emerald-600 focus:ring-emerald-500/30">
                            </td>
                            <td class="px-3 py-3 align-top whitespace-nowrap">
                                <div class="text-xs font-medium text-slate-800 dark:text-slate-200">{{ $tokens[$m->token] ?? Str::limit($m->token, 14) }}</div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $m->sessionId ?: 'Auto device' }}</div>
                            </td>
                            <td class="px-3 py-3 align-top whitespace-nowrap">
                                @if($isGrp)
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1 rounded-md bg-purple-50 dark:bg-purple-500/10 px-1.5 py-0.5 text-[10px] font-medium text-purple-700 dark:text-purple-300 ring-1 ring-inset ring-purple-200 dark:ring-purple-500/20">
                                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                                            Grup
                                        </span>
                                        <span class="text-xs font-semibold text-slate-800 dark:text-slate-100 truncate max-w-[170px]" title="{{ $m->name ?: $displayNum }}">{{ $m->name ?: 'Grup WhatsApp' }}</span>
                                    </div>
                                    <div class="text-[11px] font-mono text-slate-400 dark:text-slate-500 truncate max-w-[170px]" title="{{ $displayNum }}">{{ $displayNum }}</div>
                                    @if($m->sender)
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5">Dari: +{{ $m->sender }}</div>
                                    @endif
                                @else
                                    <div class="flex items-center gap-1.5">
                                        <span class="inline-flex items-center gap-0.5 rounded-md bg-sky-50 dark:bg-sky-500/10 px-1.5 py-0.5 text-[10px] font-medium text-sky-700 dark:text-sky-300 ring-1 ring-inset ring-sky-200 dark:ring-sky-500/20">
                                            Personal
                                        </span>
                                        <span class="text-xs font-medium text-slate-900 dark:text-slate-100 tabular-nums">{{ $displayNum }}</span>
                                    </div>
                                    @if($m->name)
                                        <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[170px]">{{ $m->name }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-3 align-top max-w-xs">
                                <p class="text-xs text-slate-600 dark:text-slate-300 line-clamp-2">{{ $m->message }}</p>
                                @if($m->error)
                                    <p class="mt-1 text-[11px] text-rose-600 dark:text-rose-400 truncate" title="{{ $m->error }}">{{ $m->error }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 align-top whitespace-nowrap">
                                @if($m->sent == 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/20">
                                        Antrian
                                    </span>
                                @elseif($m->status === 'FAILED' || $m->sent == 99)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 dark:bg-rose-500/10 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:text-rose-300 ring-1 ring-inset ring-rose-200 dark:ring-rose-500/20">
                                        Gagal
                                    </span>
                                @elseif($m->status === 'RECEIVED')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-violet-50 dark:bg-violet-500/10 px-2 py-0.5 text-[11px] font-medium text-violet-700 dark:text-violet-300 ring-1 ring-inset ring-violet-200 dark:ring-violet-500/20">
                                        Diterima
                                    </span>
                                @elseif($m->status == '4')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 dark:bg-sky-500/10 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:text-sky-300 ring-1 ring-inset ring-sky-200 dark:ring-sky-500/20">
                                        Dibaca
                                    </span>
                                @elseif($m->status == '3')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-300 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-500/20">
                                        Terkirim
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-400">
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-3 align-top whitespace-nowrap text-xs text-slate-400 dark:text-slate-500 tabular-nums">
                                <div>{{ $m->created_at?->format('d M Y') }}</div>
                                <div class="text-[11px]">{{ $m->created_at?->format('H:i:s') }}</div>
                            </td>
                            <td class="px-4 py-3 align-top whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button wire:click="openViewModal({{ $m->id }})" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition" title="Detail">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        </svg>
                                    </button>
                                    @if($m->sent == 0)
                                        <button wire:click="openEditModal({{ $m->id }})" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-200 transition" title="Edit">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" />
                                            </svg>
                                        </button>
                                    @endif
                                    @if($m->status === 'FAILED' || $m->sent == 99)
                                        <button wire:click="retrySingle({{ $m->id }})" class="p-1.5 rounded-lg text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10 hover:text-amber-700 dark:hover:text-amber-300 transition" title="Kirim Ulang">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Tidak ada pesan ditemukan</p>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Ubah filter pencarian atau kirim pesan baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($messages->hasPages())
            <div class="border-t border-slate-100 dark:border-slate-800 px-4 py-3">
                {{ $messages->links() }}
            </div>
        @endif
    </div>

    {{-- Modal: Create / Edit Pesan (Personal & Group) --}}
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 md:p-6 bg-slate-900/50 dark:bg-black/75 backdrop-blur-[1px] overflow-y-auto" x-data @keydown.escape.window="$wire.set('showFormModal', false)">
            <div class="card w-full max-w-lg sm:max-w-xl my-auto max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3rem)] flex flex-col overflow-hidden shadow-2xl" @click.outside="$wire.set('showFormModal', false)">
                <div class="flex items-center justify-between px-4 sm:px-5 py-3.5 sm:py-4 border-b border-slate-100 dark:border-slate-800 shrink-0 bg-white dark:bg-slate-900">
                    <div>
                        <h3 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white">
                            {{ $editingId ? 'Edit Pesan Antrian' : 'Kirim Pesan WhatsApp' }}
                        </h3>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Mendukung pengiriman ke Chat Personal atau Grup WhatsApp</p>
                    </div>
                    <button wire:click="$set('showFormModal', false)" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200 transition" aria-label="Tutup">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="saveMessage" class="flex flex-col flex-1 overflow-hidden min-h-0">
                    <div class="p-4 sm:p-5 space-y-4 overflow-y-auto flex-1 overscroll-contain">
                        {{-- Target Type Selector Tabs --}}
                        <div>
                            <label class="label-field mb-1.5">Tujuan Pengiriman</label>
                            <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-slate-100 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700/60">
                                <button
                                    type="button"
                                    wire:click="setTargetType('personal')"
                                    class="inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs font-medium transition {{ $formTargetType === 'personal' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                                >
                                    <svg class="h-4 w-4 text-sky-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" /></svg>
                                    <span>Chat Personal</span>
                                </button>
                                <button
                                    type="button"
                                    wire:click="setTargetType('group')"
                                    class="inline-flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs font-medium transition {{ $formTargetType === 'group' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}"
                                >
                                    <svg class="h-4 w-4 text-purple-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                                    <span>Grup WhatsApp</span>
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="label-field">Aplikasi</label>
                                <select wire:model="formToken" class="input-field" required>
                                    <option value="">Pilih aplikasi...</option>
                                    @foreach($tokens as $tokValue => $tokName)
                                        <option value="{{ $tokValue }}">{{ $tokName }}</option>
                                    @endforeach
                                </select>
                                @error('formToken') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="label-field mb-0">Device</label>
                                    @if($formTargetType === 'group')
                                        <button
                                            type="button"
                                            wire:click="loadDeviceGroups"
                                            wire:loading.attr="disabled"
                                            wire:target="loadDeviceGroups"
                                            class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1"
                                        >
                                            <span wire:loading.remove wire:target="loadDeviceGroups">🔄 Muat Grup</span>
                                            <span wire:loading wire:target="loadDeviceGroups">Memuat...</span>
                                        </button>
                                    @endif
                                </div>
                                <select wire:model="formSessionId" class="input-field">
                                    <option value="">Otomatis (sesuai grup device)</option>
                                    @foreach($devices as $devId)
                                        <option value="{{ $devId }}">{{ $devId }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Personal Target Input --}}
                        @if($formTargetType === 'personal')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="label-field">Nomor HP</label>
                                    <div class="flex">
                                        <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-200 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800 px-3 text-xs font-medium text-slate-500 dark:text-slate-400">+62</span>
                                        <input wire:model="formNumber" type="text" placeholder="81234567890" class="input-field rounded-l-none" required>
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Tanpa 0 di depan, atau awali dengan + untuk nomor internasional.</p>
                                    @error('formNumber') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="label-field">Nama Penerima (Opsional)</label>
                                    <input wire:model="formName" type="text" placeholder="Contoh: Budi Santoso" class="input-field">
                                </div>
                            </div>
                        @else
                            {{-- Group Target Input --}}
                            <div class="space-y-4 rounded-xl border border-purple-200/70 dark:border-purple-500/25 bg-purple-50/40 dark:bg-purple-500/5 p-3.5">
                                @if(!empty($deviceGroups))
                                    <div>
                                        <label class="label-field text-purple-900 dark:text-purple-200">Pilih dari Daftar Grup WhatsApp</label>
                                        <select wire:model.live="formGroupJid" class="input-field">
                                            <option value="">-- Pilih Grup yang Terdeteksi ({{ count($deviceGroups) }}) --</option>
                                            @foreach($deviceGroups as $grp)
                                                <option value="{{ $grp['id'] }}">{{ $grp['subject'] }} ({{ $grp['participantsCount'] }} anggota)</option>
                                            @endforeach
                                            <option value="manual">+ Masukkan ID Grup Manual</option>
                                        </select>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 bg-white/70 dark:bg-slate-800/60 p-2.5 rounded-lg border border-slate-200/60 dark:border-slate-700/60">
                                        <span>Grup belum dimuat. Klik tombol untuk mengambil grup dari WhatsApp.</span>
                                        <button type="button" wire:click="loadDeviceGroups" class="btn-secondary text-[11px] py-1 px-2.5">
                                            Ambil Grup
                                        </button>
                                    </div>
                                @endif

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="label-field">ID Grup WhatsApp (JID)</label>
                                        <input wire:model="formNumber" type="text" placeholder="120363025123456789@g.us" class="input-field font-mono text-xs" required>
                                        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Format JID grup WhatsApp diakhiri dengan <code>@g.us</code>.</p>
                                        @error('formNumber') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                                    </div>
                                    <div>
                                        <label class="label-field">Nama Grup (Opsional)</label>
                                        <input wire:model="formName" type="text" placeholder="Contoh: Grup Alumni / Info Kantor" class="input-field">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="label-field">Isi Pesan</label>
                            <textarea wire:model="formMessage" rows="5" placeholder="Tulis pesan WhatsApp (*tebal*, _miring_, ~coret~)..." class="input-field" required></textarea>
                            @error('formMessage') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 px-4 sm:px-5 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                        <button type="button" wire:click="$set('showFormModal', false)" class="btn-secondary text-xs">Batal</button>
                        <button type="submit" class="btn-primary text-xs">
                            {{ $editingId ? 'Simpan Perubahan' : 'Masukkan Antrian' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal: Detail Pesan --}}
    @if($showViewModal && $viewingMessage)
        @php
            $vNum = (string) $viewingMessage->number;
            $vIsGroup = (bool) ($viewingMessage->is_group || str_contains($vNum, '@g.us') || str_contains($vNum, '-'));
            $vDisplayNum = $vIsGroup ? $vNum : (($vNum !== '' && $vNum[0] === '+') ? $vNum : '+62' . $vNum);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 md:p-6 bg-slate-900/50 dark:bg-black/75 backdrop-blur-[1px] overflow-y-auto" x-data @keydown.escape.window="$wire.set('showViewModal', false)">
            <div class="card w-full max-w-lg sm:max-w-xl md:max-w-2xl my-auto max-h-[calc(100vh-2rem)] sm:max-h-[calc(100vh-3rem)] flex flex-col overflow-hidden shadow-2xl" @click.outside="$wire.set('showViewModal', false)">
                {{-- Header Modal (Sticky/Fixed) --}}
                <div class="flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-slate-100 dark:border-slate-800 shrink-0 bg-white dark:bg-slate-900">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white">Detail Pesan #{{ $viewingMessage->id }}</h3>
                            @if($vIsGroup)
                                <span class="rounded-full bg-purple-50 dark:bg-purple-500/10 px-2 py-0.5 text-[10px] font-medium text-purple-700 dark:text-purple-300 ring-1 ring-inset ring-purple-200 dark:ring-purple-500/20">Grup WA</span>
                            @else
                                <span class="rounded-full bg-sky-50 dark:bg-sky-500/10 px-2 py-0.5 text-[10px] font-medium text-sky-700 dark:text-sky-300 ring-1 ring-inset ring-sky-200 dark:ring-sky-500/20">Personal</span>
                            @endif

                            @if($viewingMessage->sent == 0)
                                <span class="rounded-full bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/20">Antrian</span>
                            @elseif($viewingMessage->status === 'FAILED' || $viewingMessage->sent == 99)
                                <span class="rounded-full bg-rose-50 dark:bg-rose-500/10 px-2 py-0.5 text-[10px] font-medium text-rose-700 dark:text-rose-300 ring-1 ring-inset ring-rose-200 dark:ring-rose-500/20">Gagal</span>
                            @elseif($viewingMessage->status === 'RECEIVED')
                                <span class="rounded-full bg-violet-50 dark:bg-violet-500/10 px-2 py-0.5 text-[10px] font-medium text-violet-700 dark:text-violet-300 ring-1 ring-inset ring-violet-200 dark:ring-violet-500/20">Diterima</span>
                            @elseif($viewingMessage->status == '4')
                                <span class="rounded-full bg-sky-50 dark:bg-sky-500/10 px-2 py-0.5 text-[10px] font-medium text-sky-700 dark:text-sky-300 ring-1 ring-inset ring-sky-200 dark:ring-sky-500/20">Dibaca</span>
                            @elseif($viewingMessage->status == '3')
                                <span class="rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:text-emerald-300 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-500/20">Terkirim</span>
                            @else
                                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:text-slate-400">Pending</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 tabular-nums">{{ $viewingMessage->created_at?->format('d F Y, H:i:s') }}</p>
                    </div>
                    <button wire:click="$set('showViewModal', false)" class="p-1.5 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200 transition" aria-label="Tutup">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body Modal (Scrollable) --}}
                <div class="p-4 sm:p-6 space-y-4 text-xs overflow-y-auto flex-1 overscroll-contain">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 p-3 sm:p-3.5 border border-slate-100 dark:border-slate-800">
                        <div>
                            <dt class="text-slate-400 dark:text-slate-500">Aplikasi</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-100 break-words">{{ $viewingMessage->app->name ?? $viewingMessage->token }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 dark:text-slate-500">Device</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-100">{{ $viewingMessage->sessionId ?: 'Auto' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 dark:text-slate-500">{{ $vIsGroup ? 'ID Grup (JID)' : 'Nomor HP' }}</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-100 {{ $vIsGroup ? 'font-mono text-[11px] break-all' : 'tabular-nums' }}">{{ $vDisplayNum }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 dark:text-slate-500">{{ $vIsGroup ? 'Nama Grup' : 'Penerima' }}</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-100">{{ $viewingMessage->name ?: '—' }}</dd>
                        </div>
                        @if($viewingMessage->sender)
                            <div class="sm:col-span-2 pt-1 border-t border-slate-200/50 dark:border-slate-700/50 flex justify-between">
                                <dt class="text-slate-400 dark:text-slate-500">Pengirim di dalam Grup</dt>
                                <dd class="font-medium text-slate-800 dark:text-slate-200 tabular-nums">+{{ $viewingMessage->sender }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-slate-400 dark:text-slate-500 font-medium">Isi Pesan</span>
                            <span class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">{{ strlen($viewingMessage->message) }} karakter</span>
                        </div>
                        {{-- Scrollable container khusus isi pesan panjang --}}
                        <div class="rounded-xl border border-emerald-200/60 dark:border-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-500/5 p-3.5 sm:p-4 text-xs sm:text-sm text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-line break-words max-h-48 sm:max-h-64 md:max-h-80 overflow-y-auto overscroll-contain shadow-inner selection:bg-emerald-200 dark:selection:bg-emerald-800">
                            {!! \App\Services\WhatsAppService::format_message(e($viewingMessage->message)) !!}
                        </div>
                    </div>

                    @if($viewingMessage->error)
                        <div class="rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 p-3 text-rose-700 dark:text-rose-300">
                            <div class="font-semibold mb-0.5">Pesan Error</div>
                            <p class="font-mono text-[11px] break-all">{{ $viewingMessage->error }}</p>
                        </div>
                    @endif
                </div>

                {{-- Footer Modal (Sticky/Fixed) --}}
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 sm:px-6 py-3 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                    <button wire:click="deleteSingle({{ $viewingMessage->id }})" wire:confirm="Hapus pesan ini?" class="text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700 dark:hover:text-rose-300 transition">
                        Hapus Pesan
                    </button>
                    <div class="flex items-center gap-2">
                        @if($viewingMessage->status === 'FAILED' || $viewingMessage->sent == 99)
                            <button wire:click="retrySingle({{ $viewingMessage->id }})" class="btn-primary text-xs">
                                Kirim Ulang
                            </button>
                        @endif
                        <button wire:click="$set('showViewModal', false)" class="btn-secondary text-xs">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
