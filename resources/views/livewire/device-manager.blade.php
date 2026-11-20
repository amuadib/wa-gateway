<div wire:poll.5s class="space-y-5">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Perangkat WhatsApp</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Kelola multi-session Baileys v7 dan scan QR Code secara langsung</p>
        </div>
        <button wire:click="openCreateModal" class="btn-primary text-xs">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            <span>Tambah Device</span>
        </button>
    </div>

    {{-- Engine Status & PM2 Control Bar --}}
    <div class="card px-4 py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 {{ $engine['online'] ? 'border-emerald-200/70 dark:border-emerald-500/20 bg-emerald-50/30 dark:bg-emerald-500/5' : 'border-rose-200/80 dark:border-rose-500/25 bg-rose-50/50 dark:bg-rose-500/10' }}">
        <div class="flex items-center gap-2.5 text-xs">
            <span class="relative flex h-2.5 w-2.5 shrink-0">
                @if($engine['online'])
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                @else
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                @endif
            </span>
            <div>
                <span class="font-semibold {{ $engine['online'] ? 'text-emerald-900 dark:text-emerald-300' : 'text-rose-900 dark:text-rose-300' }}">
                    Engine Baileys v7 {{ $engine['online'] ? 'Online' : 'Offline' }}
                </span>
                @if($engine['online'])
                    <span class="text-slate-500 dark:text-slate-400 ml-1.5 tabular-nums">
                        &middot; Uptime {{ gmdate('H:i:s', $engine['uptime']) }} &middot; {{ $engine['connectedSessions'] }}/{{ $engine['activeSessions'] }} sesi aktif
                    </span>
                @else
                    <span class="text-rose-700 dark:text-rose-300/80 ml-1.5">
                        &middot; Service PM2 belum berjalan di {{ env('BAILEYS_V7_API_URL', 'http://127.0.0.1:3000') }}
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-1.5 shrink-0">
            @if(!$engine['online'])
                <button
                    wire:click="startBaileys"
                    wire:loading.attr="disabled"
                    wire:target="startBaileys,restartBaileys,killBaileys"
                    class="btn-primary text-xs py-1.5 px-3 gap-1.5"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                    <span wire:loading.remove wire:target="startBaileys">Start</span>
                    <span wire:loading wire:target="startBaileys">Starting...</span>
                </button>
            @endif
            <button
                wire:click="restartBaileys"
                wire:loading.attr="disabled"
                wire:target="startBaileys,restartBaileys,killBaileys"
                class="btn-secondary text-xs py-1.5 px-3 gap-1.5"
            >
                <svg class="h-3.5 w-3.5" wire:loading.class="animate-spin" wire:target="restartBaileys" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                <span wire:loading.remove wire:target="restartBaileys">Restart</span>
                <span wire:loading wire:target="restartBaileys">Restarting...</span>
            </button>
            @if(auth()->user()?->isAdmin())
                <button
                    wire:click="killBaileys"
                    wire:confirm="Matikan proses Baileys (wa-baileys)? Semua sesi WhatsApp akan terputus sementara."
                    wire:loading.attr="disabled"
                    wire:target="startBaileys,restartBaileys,killBaileys"
                    class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-200 dark:border-rose-500/30 bg-white dark:bg-rose-500/10 px-3 py-1.5 text-xs font-medium text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-500/20 disabled:opacity-50 transition"
                >
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" /></svg>
                    <span wire:loading.remove wire:target="killBaileys">Kill</span>
                    <span wire:loading wire:target="killBaileys">Killing...</span>
                </button>
            @endif
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card p-3.5 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-1.5">
            @foreach(['all' => 'Semua', 'Connected' => 'Connected', 'Pending' => 'Pending', 'Disconnected' => 'Disconnected'] as $stKey => $stLabel)
                <button
                    wire:click="$set('statusFilter', '{{ $stKey }}')"
                    class="px-3 py-1 rounded-full text-xs font-medium transition {{ $statusFilter === $stKey ? 'bg-slate-900 dark:bg-emerald-600 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200/70 dark:hover:bg-slate-700' }}"
                >
                    {{ $stLabel }}
                </button>
            @endforeach
        </div>
        <div class="sm:w-52">
            <select wire:model.live="groupFilter" class="input-field py-1.5 text-xs">
                <option value="">Semua Kelompok</option>
                @foreach($groups as $grp)
                    <option value="{{ $grp->id }}">{{ $grp->nama }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Device Cards Grid --}}
    @if($devices->isEmpty())
        <div class="card p-12 text-center">
            <div class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 mb-3">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                </svg>
            </div>
            <p class="text-sm font-medium text-slate-700 dark:text-slate-200">Belum ada device WhatsApp</p>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Tambahkan device baru lalu scan QR Code untuk mulai mengirim pesan.</p>
            <button wire:click="openCreateModal" class="btn-primary text-xs mt-4">Tambah Device Pertama</button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($devices as $device)
                @php
                    $isConnected = $device->status === 'Connected';
                    $isPending = $device->status === 'Pending';
                    $canManage = $this->canManage($device);
                @endphp
                <div wire:key="device-{{ $device->id }}" class="card p-5 flex flex-col justify-between gap-4">
                    <div>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-slate-900 dark:text-white truncate">{{ $device->id }}</h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">{{ $device->grup->nama ?? 'Tanpa Kelompok' }}</p>
                            </div>
                            <span class="shrink-0 inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium ring-1 ring-inset
                                {{ $isConnected ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 ring-emerald-200 dark:ring-emerald-500/20' : ($isPending ? 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 ring-amber-200 dark:ring-amber-500/20' : 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300 ring-rose-200 dark:ring-rose-500/20') }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $isConnected ? 'bg-emerald-500' : ($isPending ? 'bg-amber-500 animate-pulse' : 'bg-rose-500') }}"></span>
                                {{ $device->status ?: 'Disconnected' }}
                            </span>
                        </div>

                        {{-- QR Area / Connection Info --}}
                        <div class="mt-4">
                            @if($isConnected)
                                <div class="rounded-lg bg-emerald-50/50 dark:bg-emerald-500/10 border border-emerald-100 dark:border-emerald-500/20 p-3.5 flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-lg bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 flex items-center justify-center shrink-0">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    </div>
                                    <div class="text-xs">
                                        <div class="font-medium text-emerald-900 dark:text-emerald-200">Tersambung & Siap Kirim</div>
                                        <div class="text-emerald-700/70 dark:text-emerald-400/80 mt-0.5">Sesi aktif di Baileys v7</div>
                                    </div>
                                </div>
                            @elseif(!empty($device->qr))
                                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40 p-3 flex flex-col items-center text-center">
                                    <button type="button" wire:click="$set('zoomQrId', '{{ $device->id }}')" class="group relative rounded-lg bg-white p-2.5 shadow-sm border border-slate-200/80 hover:border-emerald-400 transition" title="Klik untuk memperbesar QR">
                                        <img src="{{ $device->qr }}" alt="QR {{ $device->id }}" class="h-36 w-36 object-contain">
                                        <span class="mt-1.5 block text-[11px] font-medium text-slate-600 group-hover:text-emerald-600">Klik perbesar QR</span>
                                    </button>
                                </div>
                            @else
                                <div class="rounded-lg bg-slate-50 dark:bg-slate-800/60 border border-slate-100 dark:border-slate-800 p-3.5 text-xs text-slate-500 dark:text-slate-400">
                                    @if($isPending)
                                        Menunggu QR Code dari Baileys... (auto-refresh setiap 5 detik)
                                    @else
                                        <div>Perangkat terputus.</div>
                                        @if($device->disconnected_at)
                                            <div class="mt-1 text-[11px] text-slate-400 dark:text-slate-500 tabular-nums">Terputus: {{ $device->disconnected_at }}</div>
                                        @endif
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center justify-between gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                        <button wire:click="syncStatus('{{ $device->id }}')" wire:loading.attr="disabled" class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition">
                            Cek Status
                        </button>
                        @if($canManage)
                            <div class="flex items-center gap-1.5">
                                @if(!$isConnected)
                                    <button wire:click="reconnectDevice('{{ $device->id }}')" wire:loading.attr="disabled" class="btn-secondary text-xs py-1.5 px-2.5">
                                        Reconnect
                                    </button>
                                @endif
                                <button wire:click="deleteDevice('{{ $device->id }}')" wire:confirm="Hapus device {{ $device->id }} beserta sesinya?" class="px-2.5 py-1.5 rounded-lg text-xs font-medium text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition">
                                    Hapus
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal: Tambah Device --}}
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/70 backdrop-blur-[1px]" x-data @keydown.escape.window="$wire.set('showCreateModal', false)">
            <div class="card w-full max-w-md overflow-hidden shadow-xl" @click.outside="$wire.set('showCreateModal', false)">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Tambah Device WhatsApp</h3>
                    <button wire:click="$set('showCreateModal', false)" class="p-1 rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit="createDevice" class="p-5 space-y-4">
                    <div>
                        <label class="label-field">Nama Device</label>
                        <input wire:model="deviceId" type="text" placeholder="Contoh: CS-Utama" class="input-field" required>
                        @error('deviceId') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
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

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="$set('showCreateModal', false)" class="btn-secondary text-xs">Batal</button>
                        <button type="submit" class="btn-primary text-xs">Buat Sesi & Generate QR</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal: Zoom QR Code --}}
    @if($zoomDevice && !empty($zoomDevice->qr))
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 dark:bg-black/75 backdrop-blur-[2px]" x-data @keydown.escape.window="$wire.set('zoomQrId', null)">
            <div class="card w-full max-w-sm p-6 text-center shadow-2xl" @click.outside="$wire.set('zoomQrId', null)">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Scan QR — {{ $zoomDevice->id }}</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Buka WhatsApp &rarr; Perangkat Tertaut &rarr; Tautkan Perangkat</p>
                <div class="my-5 inline-block rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <img src="{{ $zoomDevice->qr }}" alt="QR {{ $zoomDevice->id }}" class="h-64 w-64 object-contain">
                </div>
                <div>
                    <button wire:click="$set('zoomQrId', null)" class="btn-secondary text-xs w-full">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>
