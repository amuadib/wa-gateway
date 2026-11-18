<div wire:poll.15s class="space-y-5">
    {{-- Header Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Antrian & Riwayat Pesan</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pantau status pengiriman WhatsApp secara real-time
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
    <div class="card p-4 space-y-3">
        <div class="flex flex-col md:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Cari nomor HP, nama penerima, device, atau isi pesan..."
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

        {{-- Status Filter Pills --}}
        @php
            $statusTabs = [
                'all' => 'Semua',
                'queued' => 'Antrian',
                'PENDING' => 'Pending',
                '3' => 'Terkirim',
                '4' => 'Dibaca',
                'FAILED' => 'Gagal',
            ];
        @endphp
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach($statusTabs as $key => $label)
                <button
                    wire:click="$set('statusFilter', '{{ $key }}')"
                    class="px-3 py-1 rounded-full text-xs font-medium transition {{ $statusFilter === $key ? 'bg-slate-900 dark:bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
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
                        <th class="px-3 py-3">Penerima</th>
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
                            $displayNum = ($num !== '' && $num[0] === '+') ? $num : '+62' . $num;
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
                                <div class="text-xs font-medium text-slate-900 dark:text-slate-100 tabular-nums">{{ $displayNum }}</div>
                                @if($m->name)
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[160px]">{{ $m->name }}</div>
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

    {{-- Modal: Create / Edit Pesan --}}
    @if($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/70 backdrop-blur-[1px]" x-data @keydown.escape.window="$wire.set('showFormModal', false)">
            <div class="card w-full max-w-lg overflow-hidden shadow-xl" @click.outside="$wire.set('showFormModal', false)">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">
                        {{ $editingId ? 'Edit Pesan Antrian' : 'Kirim Pesan Baru' }}
                    </h3>
                    <button wire:click="$set('showFormModal', false)" class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="saveMessage" class="p-5 space-y-4">
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
                            <label class="label-field">Device (Opsional)</label>
                            <select wire:model="formSessionId" class="input-field">
                                <option value="">Otomatis (sesuai grup)</option>
                                @foreach($devices as $devId)
                                    <option value="{{ $devId }}">{{ $devId }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label-field">Nomor HP</label>
                            <div class="flex">
                                <span class="inline-flex items-center rounded-l-lg border border-r-0 border-slate-200 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-800 px-3 text-xs font-medium text-slate-500 dark:text-slate-400">+62</span>
                                <input wire:model="formNumber" type="text" placeholder="81234567890" class="input-field rounded-l-none" required>
                            </div>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">Tanpa awalan 0, atau awali dengan + untuk luar negeri.</p>
                            @error('formNumber') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label-field">Nama Penerima (Opsional)</label>
                            <input wire:model="formName" type="text" placeholder="Nama penerima" class="input-field">
                        </div>
                    </div>

                    <div>
                        <label class="label-field">Isi Pesan</label>
                        <textarea wire:model="formMessage" rows="4" placeholder="Tulis pesan WhatsApp (*tebal*, _miring_, ~coret~)..." class="input-field" required></textarea>
                        @error('formMessage') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
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
            $vDisplayNum = ($vNum !== '' && $vNum[0] === '+') ? $vNum : '+62' . $vNum;
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/70 backdrop-blur-[1px]" x-data @keydown.escape.window="$wire.set('showViewModal', false)">
            <div class="card w-full max-w-lg overflow-hidden shadow-xl" @click.outside="$wire.set('showViewModal', false)">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Detail Pesan #{{ $viewingMessage->id }}</h3>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 tabular-nums">{{ $viewingMessage->created_at?->format('d F Y, H:i:s') }}</p>
                    </div>
                    <button wire:click="$set('showViewModal', false)" class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="p-5 space-y-4 text-xs">
                    <dl class="grid grid-cols-2 gap-3 rounded-lg bg-slate-50 dark:bg-slate-800/60 p-3.5 border border-slate-100 dark:border-slate-800">
                        <div>
                            <dt class="text-slate-400 dark:text-slate-500">Aplikasi</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-100">{{ $viewingMessage->app->name ?? $viewingMessage->token }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 dark:text-slate-500">Device</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-100">{{ $viewingMessage->sessionId ?: 'Auto' }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 dark:text-slate-500">Nomor HP</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-100 tabular-nums">{{ $vDisplayNum }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-400 dark:text-slate-500">Penerima</dt>
                            <dd class="mt-0.5 font-medium text-slate-800 dark:text-slate-100">{{ $viewingMessage->name ?: '—' }}</dd>
                        </div>
                    </dl>

                    <div>
                        <div class="text-slate-400 dark:text-slate-500 mb-1.5 font-medium">Isi Pesan</div>
                        <div class="rounded-xl border border-emerald-200/60 dark:border-emerald-500/20 bg-emerald-50/40 dark:bg-emerald-500/5 p-4 text-sm text-slate-800 dark:text-slate-200 leading-relaxed whitespace-pre-line">
                            {!! \App\Services\WhatsAppService::format_message(e($viewingMessage->message)) !!}
                        </div>
                    </div>

                    @if($viewingMessage->error)
                        <div class="rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 p-3 text-rose-700 dark:text-rose-300">
                            <div class="font-semibold mb-0.5">Pesan Error</div>
                            <p class="font-mono text-[11px] break-all">{{ $viewingMessage->error }}</p>
                        </div>
                    @endif

                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button wire:click="deleteSingle({{ $viewingMessage->id }})" wire:confirm="Hapus pesan ini?" class="text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-rose-700">
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
        </div>
    @endif
</div>
