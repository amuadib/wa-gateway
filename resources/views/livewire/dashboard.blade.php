<div wire:poll.15s class="space-y-6">
    {{-- Top KPI Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Pesan --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Pesan</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 0 1 .778-.332 48.294 48.294 0 0 0 5.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-slate-900 dark:text-white tabular-nums">{{ number_format($stats['total']) }}</span>
                @if($stats['queued'] > 0)
                    <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/20">
                        {{ number_format($stats['queued']) }} antrian
                    </span>
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $totalApps }} aplikasi &middot; {{ $totalGroups }} kelompok</p>
        </div>

        {{-- Terkirim & Dibaca --}}
        @php
            $successCount = $stats['delivered'] + $stats['read'];
            $successRate = $stats['total'] > 0 ? round(($successCount / $stats['total']) * 100, 1) : 0;
        @endphp
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Terkirim & Dibaca</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-slate-900 dark:text-white tabular-nums">{{ number_format($successCount) }}</span>
                <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $successRate }}%</span>
            </div>
            <p class="mt-1 text-xs text-slate-400 dark:text-slate-500 tabular-nums">{{ number_format($stats['delivered']) }} terkirim &middot; {{ number_format($stats['read']) }} dibaca</p>
        </div>

        {{-- Gagal --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Gagal Terkirim</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 dark:bg-rose-500/10 text-rose-600 dark:text-rose-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-2">
                <span class="text-2xl font-semibold text-slate-900 dark:text-white tabular-nums">{{ number_format($stats['failed']) }}</span>
                @if($stats['pending'] > 0)
                    <span class="text-xs text-slate-400 dark:text-slate-500 tabular-nums">{{ number_format($stats['pending']) }} pending</span>
                @endif
            </div>
            <p class="mt-1 text-xs text-slate-400">
                <a href="{{ route('messages.index', ['statusFilter' => 'FAILED']) }}" class="text-slate-500 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition">Lihat pesan gagal &rarr;</a>
            </p>
        </div>

        {{-- Device Terhubung --}}
        <div class="card p-5">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Device Terhubung</span>
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg {{ $connectedDevices > 0 ? 'bg-sky-50 dark:bg-sky-500/10 text-sky-600 dark:text-sky-400' : 'bg-amber-50 dark:bg-amber-500/10 text-amber-600 dark:text-amber-400' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                </span>
            </div>
            <div class="mt-3 flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold text-slate-900 dark:text-white tabular-nums">{{ $connectedDevices }}</span>
                <span class="text-sm text-slate-400 dark:text-slate-500 tabular-nums">/ {{ $totalDevices }}</span>
            </div>
            <p class="mt-1 text-xs text-slate-400">
                <a href="{{ route('devices.index') }}" class="text-slate-500 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition">Kelola device &amp; QR &rarr;</a>
            </p>
        </div>
    </div>

    {{-- Middle Grid: Delivery Chart + Engine & Devices --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Distribusi Status Pesan (Lightweight SVG Donut) --}}
        @php
            $chartTotal = max(1, $stats['pending'] + $stats['queued'] + $stats['delivered'] + $stats['read'] + $stats['failed']);
            $segments = [
                ['label' => 'Dibaca', 'count' => $stats['read'], 'color' => '#0ea5e9', 'dot' => 'bg-sky-500'],
                ['label' => 'Terkirim', 'count' => $stats['delivered'], 'color' => '#10b981', 'dot' => 'bg-emerald-500'],
                ['label' => 'Antrian / Pending', 'count' => $stats['queued'] + $stats['pending'], 'color' => '#f59e0b', 'dot' => 'bg-amber-500'],
                ['label' => 'Gagal', 'count' => $stats['failed'], 'color' => '#f43f5e', 'dot' => 'bg-rose-500'],
            ];
            $circumference = 2 * pi() * 40;
            $offset = 0;
        @endphp
        <div class="card p-5 lg:col-span-2 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Distribusi Status Pengiriman</h2>
                    <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">Ringkasan status seluruh pesan yang tercatat</p>
                </div>
                <a href="{{ route('messages.index') }}" class="btn-secondary text-xs py-1.5 px-3">Buka Pesan</a>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-8 py-2">
                <div class="relative h-44 w-44 shrink-0">
                    <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                        <circle cx="50" cy="50" r="40" fill="none" class="stroke-slate-100 dark:stroke-slate-800" stroke-width="12" />
                        @if($stats['total'] > 0)
                            @foreach($segments as $seg)
                                @php
                                    $pct = $seg['count'] / $chartTotal;
                                    $dash = $pct * $circumference;
                                @endphp
                                @if($seg['count'] > 0)
                                    <circle
                                        cx="50" cy="50" r="40"
                                        fill="none"
                                        stroke="{{ $seg['color'] }}"
                                        stroke-width="12"
                                        stroke-dasharray="{{ $dash }} {{ $circumference }}"
                                        stroke-dashoffset="-{{ $offset }}"
                                        class="transition-all duration-500"
                                    />
                                    @php $offset += $dash; @endphp
                                @endif
                            @endforeach
                        @endif
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-2xl font-semibold text-slate-900 dark:text-white tabular-nums">{{ number_format($stats['total']) }}</span>
                        <span class="text-[11px] font-medium text-slate-400 dark:text-slate-500">Total Pesan</span>
                    </div>
                </div>

                <div class="flex-1 w-full space-y-3">
                    @foreach($segments as $seg)
                        @php $pctNum = $stats['total'] > 0 ? round(($seg['count'] / $stats['total']) * 100, 1) : 0; @endphp
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="flex items-center gap-2 font-medium text-slate-700 dark:text-slate-300">
                                    <span class="h-2.5 w-2.5 rounded-full {{ $seg['dot'] }}"></span>
                                    {{ $seg['label'] }}
                                </span>
                                <span class="tabular-nums text-slate-500 dark:text-slate-400">
                                    <strong class="text-slate-800 dark:text-slate-100">{{ number_format($seg['count']) }}</strong>
                                    ({{ $pctNum }}%)
                                </span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                <div class="h-full rounded-full {{ $seg['dot'] }} transition-all duration-500" style="width: {{ $pctNum }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Engine Baileys & Ringkasan Device --}}
        <div class="card p-5 flex flex-col justify-between gap-5">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Status Engine Baileys</h2>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-medium {{ $engine['online'] ? 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-500/20' : 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300 ring-1 ring-inset ring-rose-200 dark:ring-rose-500/20' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $engine['online'] ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                        {{ $engine['online'] ? 'Online' : 'Offline' }}
                    </span>
                </div>

                @if($engine['online'])
                    <dl class="space-y-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-800/60 p-3 border border-slate-100 dark:border-slate-800">
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500 dark:text-slate-400">Uptime</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-100 tabular-nums">{{ gmdate('H:i:s', $engine['uptime']) }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500 dark:text-slate-400">Sesi Aktif</dt>
                            <dd class="font-medium text-slate-800 dark:text-slate-100 tabular-nums">{{ $engine['connectedSessions'] }} / {{ $engine['activeSessions'] }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-slate-500 dark:text-slate-400 shrink-0">Webhook</dt>
                            <dd class="font-mono text-[11px] text-slate-600 dark:text-slate-300 truncate" title="{{ $engine['webhookUrl'] }}">{{ $engine['webhookUrl'] ?: '-' }}</dd>
                        </div>
                    </dl>
                    <div class="mt-3 space-y-2">
                        @if(auth()->user()?->isAdmin())
                            <div class="grid grid-cols-3 gap-1.5">
                                <button
                                    wire:click="startBaileys"
                                    wire:loading.attr="disabled"
                                    wire:target="startBaileys,restartBaileys,killBaileys"
                                    disabled
                                    class="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/40 px-2.5 py-1.5 text-xs font-medium text-slate-400 dark:text-slate-600 cursor-not-allowed"
                                    title="Proses sudah berjalan"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                                    <span>Start</span>
                                </button>
                                <button
                                    wire:click="restartBaileys"
                                    wire:loading.attr="disabled"
                                    wire:target="startBaileys,restartBaileys,killBaileys"
                                    class="btn-secondary text-xs py-1.5 px-2.5 gap-1"
                                >
                                    <svg class="h-3.5 w-3.5" wire:loading.class="animate-spin" wire:target="restartBaileys" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                    <span wire:loading.remove wire:target="restartBaileys">Restart</span>
                                    <span wire:loading wire:target="restartBaileys">...</span>
                                </button>
                                <button
                                    wire:click="killBaileys"
                                    wire:confirm="Matikan proses Baileys (wa-baileys)? Semua koneksi WhatsApp akan terputus sementara."
                                    wire:loading.attr="disabled"
                                    wire:target="startBaileys,restartBaileys,killBaileys"
                                    class="inline-flex items-center justify-center gap-1 rounded-lg border border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 px-2.5 py-1.5 text-xs font-medium text-rose-700 dark:text-rose-300 hover:bg-rose-100 dark:hover:bg-rose-500/20 disabled:opacity-50 transition"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" /></svg>
                                    <span wire:loading.remove wire:target="killBaileys">Kill</span>
                                    <span wire:loading wire:target="killBaileys">...</span>
                                </button>
                            </div>
                        @endif
                        <button wire:click="testWebhook" wire:loading.attr="disabled" class="btn-secondary w-full text-xs py-1.5">
                            <span wire:loading.remove wire:target="testWebhook">Uji Koneksi Webhook</span>
                            <span wire:loading wire:target="testWebhook">Menguji...</span>
                        </button>
                    </div>
                @else
                    <div class="rounded-lg border border-rose-200/80 dark:border-rose-500/25 bg-rose-50/70 dark:bg-rose-500/10 p-3 text-xs text-rose-700 dark:text-rose-300 leading-relaxed">
                        Engine Node.js Baileys tidak merespons di <code class="font-mono">{{ env('BAILEYS_V7_API_URL', 'http://127.0.0.1:3000') }}</code>.
                    </div>
                    @if(auth()->user()?->isAdmin())
                        <div class="mt-3 grid grid-cols-3 gap-1.5">
                            <button
                                wire:click="startBaileys"
                                wire:loading.attr="disabled"
                                wire:target="startBaileys,restartBaileys,killBaileys"
                                class="btn-primary text-xs py-1.5 px-2.5 gap-1"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
                                <span wire:loading.remove wire:target="startBaileys">Start</span>
                                <span wire:loading wire:target="startBaileys">...</span>
                            </button>
                            <button
                                wire:click="restartBaileys"
                                wire:loading.attr="disabled"
                                wire:target="startBaileys,restartBaileys,killBaileys"
                                class="btn-secondary text-xs py-1.5 px-2.5 gap-1"
                            >
                                <svg class="h-3.5 w-3.5" wire:loading.class="animate-spin" wire:target="restartBaileys" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>
                                <span wire:loading.remove wire:target="restartBaileys">Restart</span>
                                <span wire:loading wire:target="restartBaileys">...</span>
                            </button>
                            <button
                                wire:click="killBaileys"
                                wire:confirm="Pastikan proses wa-baileys dihapus dari PM2?"
                                wire:loading.attr="disabled"
                                wire:target="startBaileys,restartBaileys,killBaileys"
                                class="inline-flex items-center justify-center gap-1 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-500 dark:text-slate-400 hover:bg-rose-50 dark:hover:bg-rose-500/10 hover:text-rose-700 dark:hover:text-rose-300 hover:border-rose-200 dark:hover:border-rose-500/30 disabled:opacity-50 transition"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5.636 5.636a9 9 0 1 0 12.728 0M12 3v9" /></svg>
                                <span wire:loading.remove wire:target="killBaileys">Kill</span>
                                <span wire:loading wire:target="killBaileys">...</span>
                            </button>
                        </div>
                    @endif
                @endif
            </div>

            <div class="border-t border-slate-100 dark:border-slate-800 pt-4">
                <div class="flex items-center justify-between mb-2.5">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500">Device Terdaftar</h3>
                    <a href="{{ route('devices.index') }}" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700">Semua</a>
                </div>
                @if($devices->isEmpty())
                    <p class="text-xs text-slate-400 dark:text-slate-500 py-2">Belum ada device terdaftar.</p>
                @else
                    <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                        @foreach($devices->take(5) as $dev)
                            <div class="flex items-center justify-between gap-2 text-xs py-1">
                                <div class="min-w-0">
                                    <div class="font-medium text-slate-800 dark:text-slate-200 truncate">{{ $dev->id }}</div>
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate">{{ $dev->grup->nama ?? '-' }}</div>
                                </div>
                                @php
                                    $badgeClass = match($dev->status) {
                                        'Connected' => 'bg-emerald-50 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 ring-emerald-200 dark:ring-emerald-500/20',
                                        'Pending' => 'bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 ring-amber-200 dark:ring-amber-500/20',
                                        default => 'bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-300 ring-rose-200 dark:ring-rose-500/20',
                                    };
                                @endphp
                                <span class="shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $badgeClass }}">
                                    {{ $dev->status ?: 'Offline' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Activity Table --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-white">Aktivitas Pesan Terbaru</h2>
                <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">8 pesan terakhir yang masuk ke sistem</p>
            </div>
            <a href="{{ route('messages.index') }}" class="text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700">Lihat semua &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800 text-sm">
                <thead class="bg-slate-50/70 dark:bg-slate-800/50">
                    <tr class="text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">Aplikasi</th>
                        <th class="px-5 py-3">Tujuan</th>
                        <th class="px-5 py-3">Device</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/80 bg-white dark:bg-slate-900">
                    @forelse($recentMessages as $m)
                        @php
                            $num = (string) $m->number;
                            $displayNum = ($num !== '' && $num[0] === '+') ? $num : '+62' . $num;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3 text-xs font-medium text-slate-700 dark:text-slate-300 whitespace-nowrap">
                                {{ $tokenNames[$m->token] ?? Str::limit($m->token, 14) }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="text-xs font-medium text-slate-900 dark:text-slate-100 tabular-nums">{{ $displayNum }}</div>
                                @if($m->name)
                                    <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[180px]">{{ $m->name }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-xs text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                {{ $m->sessionId ?: '—' }}
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                @if($m->sent == 0)
                                    <span class="inline-flex items-center rounded-full bg-amber-50 dark:bg-amber-500/10 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:text-amber-300 ring-1 ring-inset ring-amber-200 dark:ring-amber-500/20">Antrian</span>
                                @elseif($m->status === 'FAILED' || $m->sent == 99)
                                    <span class="inline-flex items-center rounded-full bg-rose-50 dark:bg-rose-500/10 px-2 py-0.5 text-[11px] font-medium text-rose-700 dark:text-rose-300 ring-1 ring-inset ring-rose-200 dark:ring-rose-500/20">Gagal</span>
                                @elseif($m->status == '4')
                                    <span class="inline-flex items-center rounded-full bg-sky-50 dark:bg-sky-500/10 px-2 py-0.5 text-[11px] font-medium text-sky-700 dark:text-sky-300 ring-1 ring-inset ring-sky-200 dark:ring-sky-500/20">Dibaca</span>
                                @elseif($m->status == '3')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 text-[11px] font-medium text-emerald-700 dark:text-emerald-300 ring-1 ring-inset ring-emerald-200 dark:ring-emerald-500/20">Terkirim</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-slate-100 dark:bg-slate-800 px-2 py-0.5 text-[11px] font-medium text-slate-600 dark:text-slate-400">Pending</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right text-xs text-slate-400 dark:text-slate-500 tabular-nums whitespace-nowrap">
                                {{ $m->created_at?->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center text-xs text-slate-400 dark:text-slate-500">
                                Belum ada pesan yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
