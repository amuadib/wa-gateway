<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Troubleshoot & Diagnostik</h1>
                <span class="rounded-full bg-slate-100 dark:bg-slate-800 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:text-slate-400">System Health</span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                Pemeriksaan menyeluruh semua requirement untuk menjalankan WA Gateway, Baileys v7 engine, database, dan cron antrian pesan.
            </p>
        </div>

        {{-- Top Action Buttons --}}
        <div class="flex items-center gap-2">
            <button
                wire:click="refreshDiagnostics"
                wire:loading.attr="disabled"
                class="btn-secondary text-xs sm:text-sm flex items-center gap-2"
                title="Periksa ulang semua requirement sistem"
            >
                <svg wire:loading.remove wire:target="refreshDiagnostics" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                <svg wire:loading wire:target="refreshDiagnostics" class="animate-spin h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Cek Ulang</span>
            </button>

            <button
                wire:click="executeAction('clear_cache')"
                wire:loading.attr="disabled"
                wire:target="executeAction('clear_cache')"
                class="btn-secondary text-xs sm:text-sm flex items-center gap-1.5"
                title="Hapus cache config, route, dan view"
            >
                <svg class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                <span>Clear Cache</span>
            </button>

            <button
                wire:click="executeAction('run_scheduler_once')"
                wire:loading.attr="disabled"
                wire:target="executeAction('run_scheduler_once')"
                class="btn-primary text-xs sm:text-sm flex items-center gap-1.5"
                title="Jalankan perintah scheduler sekali secara instan"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                </svg>
                <span>Test Scheduler</span>
            </button>
        </div>
    </div>

    {{-- Overall Status Banner --}}
    @php
        $status = $overall['status'] ?? 'ok';
        $statusColors = [
            'ok' => 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800/80 text-emerald-800 dark:text-emerald-200',
            'warning' => 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800/80 text-amber-800 dark:text-amber-200',
            'error' => 'bg-rose-50 dark:bg-rose-950/40 border-rose-200 dark:border-rose-800/80 text-rose-800 dark:text-rose-200',
        ];
    @endphp
    <div class="card p-4 sm:p-5 border {{ $statusColors[$status] }}">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div class="p-2 rounded-xl shrink-0 {{ $status === 'ok' ? 'bg-emerald-600 text-white' : ($status === 'warning' ? 'bg-amber-600 text-white' : 'bg-rose-600 text-white') }}">
                    @if($status === 'ok')
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    @elseif($status === 'warning')
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                        </svg>
                    @else
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    @endif
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-semibold leading-tight">
                        @if($status === 'ok')
                            Semua Kebutuhan Sistem Terpenuhi!
                        @elseif($status === 'warning')
                            Ada Catatan / Peringatan Sistem
                        @else
                            Terdapat Masalah Kritis Yang Perlu Diperbaiki!
                        @endif
                    </h3>
                    <p class="text-xs sm:text-sm mt-0.5 opacity-90">
                        @if($status === 'ok')
                            Engine Baileys, koneksi database, izin direktori, dan scheduler siap menjalankan pengiriman pesan.
                        @elseif($status === 'warning')
                            Aplikasi dapat berjalan, namun beberapa fitur opsional atau optimalisasi disarankan ditinjau ulang.
                        @else
                            Satu atau lebih komponen utama belum siap (misal Baileys offline atau cron belum berjalan). Gunakan tombol aksi atau petunjuk di bawah.
                        @endif
                    </p>
                </div>
            </div>

            {{-- Summary Badges --}}
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap text-xs">
                <span class="rounded-lg bg-white/80 dark:bg-slate-900/80 px-2.5 py-1.5 font-medium shadow-sm ring-1 ring-black/5 dark:ring-white/10">
                    Total: <strong class="font-bold text-slate-800 dark:text-slate-100">{{ $overall['total'] ?? 0 }}</strong>
                </span>
                <span class="rounded-lg bg-emerald-100/80 dark:bg-emerald-900/50 px-2.5 py-1.5 font-semibold text-emerald-800 dark:text-emerald-300">
                    Lolos: {{ $overall['passed'] ?? 0 }}
                </span>
                @if(($overall['warnings'] ?? 0) > 0)
                    <span class="rounded-lg bg-amber-100/80 dark:bg-amber-900/50 px-2.5 py-1.5 font-semibold text-amber-800 dark:text-amber-300">
                        Peringatan: {{ $overall['warnings'] }}
                    </span>
                @endif
                @if(($overall['errors'] ?? 0) > 0)
                    <span class="rounded-lg bg-rose-100/80 dark:bg-rose-900/50 px-2.5 py-1.5 font-semibold text-rose-800 dark:text-rose-300">
                        Masalah: {{ $overall['errors'] }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Category Filters --}}
    <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto pb-1 text-xs">
        @php
            $categoryLabels = [
                'all' => 'Semua Kategori',
                'baileys' => 'Baileys & Node.js',
                'scheduler' => 'Scheduler & Cron',
                'database' => 'Database & Migrasi',
                'php' => 'Lingkungan PHP',
                'storage' => 'Izin & Storage',
            ];
        @endphp

        @foreach($categoryLabels as $key => $label)
            <button
                type="button"
                wire:click="setCategory('{{ $key }}')"
                class="px-3 py-1.5 rounded-lg font-medium whitespace-nowrap transition {{ $activeCategory === $key ? 'bg-slate-900 text-white dark:bg-emerald-600 dark:text-white shadow-sm' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200/80 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Main Requirement Cards --}}
    @php
        $categoryConfig = [
            'baileys' => [
                'title' => 'Baileys v7 Engine & Process Manager',
                'desc' => 'Service Node.js penghubung protokol WhatsApp Multi-Device (Port 3000)',
                'icon' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/><path d="m13 8-2 4h3l-2 4"/>',
            ],
            'scheduler' => [
                'title' => 'Scheduler & Cron Antrian Pesan',
                'desc' => 'Otomasi pengiriman antrian pesan berkala (schedule:run & message:send-to-api)',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            ],
            'database' => [
                'title' => 'Database & Migrasi Tabel',
                'desc' => 'Konektivitas database MySQL dan integritas tabel aplikasi',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />',
            ],
            'php' => [
                'title' => 'Lingkungan PHP & Konfigurasi Server',
                'desc' => 'Versi runtime PHP, ekstensi wajib, batas memori, dan keamanan open_basedir',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 0 1 0 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 0 1 0-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />',
            ],
            'storage' => [
                'title' => 'Direktori & Hak Akses Tulis (File Permissions)',
                'desc' => 'Izin tulis direktori storage, bootstrap/cache, dan konfigurasi APP_KEY',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />',
            ],
        ];
    @endphp

    <div class="space-y-6">
        @foreach($categories as $categoryKey => $items)
            @if($activeCategory === 'all' || $activeCategory === $categoryKey)
                @php
                    $cfg = $categoryConfig[$categoryKey] ?? ['title' => ucfirst($categoryKey), 'desc' => '', 'icon' => ''];
                @endphp
                <div class="card overflow-hidden">
                    {{-- Category Header --}}
                    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    {!! $cfg['icon'] !!}
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white">{{ $cfg['title'] }}</h2>
                                <p class="text-[11px] sm:text-xs text-slate-400 dark:text-slate-500">{{ $cfg['desc'] }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Checklist Items --}}
                    <div class="divide-y divide-slate-100 dark:divide-slate-800/80">
                        @foreach($items as $itemKey => $item)
                            <div class="p-4 sm:p-5 flex flex-col gap-3 hover:bg-slate-50/40 dark:hover:bg-slate-800/30 transition">
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                    {{-- Left: Status & Detail --}}
                                    <div class="flex items-start gap-3 min-w-0 flex-1">
                                        {{-- Badge Icon --}}
                                        <div class="mt-0.5 shrink-0">
                                            @if($item['status'] === 'ok')
                                                <div class="h-6 w-6 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center" title="Lolos / Memenuhi Syarat">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                                    </svg>
                                                </div>
                                            @elseif($item['status'] === 'warning')
                                                <div class="h-6 w-6 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center" title="Peringatan / Disarankan Ditinjau">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                                    </svg>
                                                </div>
                                            @else
                                                <div class="h-6 w-6 rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 flex items-center justify-center" title="Error / Masalah Kritis">
                                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="text-xs sm:text-sm font-semibold text-slate-900 dark:text-white">{{ $item['name'] }}</h4>
                                                <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase {{ $item['status'] === 'ok' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 ring-1 ring-emerald-600/20' : ($item['status'] === 'warning' ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 ring-1 ring-amber-600/20' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400 ring-1 ring-rose-600/20') }}">
                                                    {{ $item['value'] }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">{{ $item['detail'] }}</p>
                                        </div>
                                    </div>

                                    {{-- Right: Direct Actions (if any) --}}
                                    @if(!empty($item['actions']))
                                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-start">
                                            @foreach($item['actions'] as $action)
                                                @php
                                                    $actionLabels = [
                                                        'start_engine' => 'Start Engine',
                                                        'restart_engine' => 'Restart Engine',
                                                        'kill_engine' => 'Kill Engine',
                                                        'run_scheduler_once' => 'Jalankan Sekali',
                                                        'run_queue_once' => 'Proses Antrian',
                                                        'run_migrations' => 'Jalankan Migrasi',
                                                        'clear_cache' => 'Clear Cache',
                                                        'fix_sessions_dir' => 'Buat Folder Sesi',
                                                        'test_storage_write' => 'Uji Tulis',
                                                        'generate_app_key' => 'Generate Key',
                                                    ];
                                                    $isDestructive = in_array($action, ['kill_engine']);
                                                @endphp
                                                <button
                                                    wire:click="executeAction('{{ $action }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="executeAction('{{ $action }}')"
                                                    class="{{ $isDestructive ? 'btn-danger' : 'btn-secondary' }} text-xs py-1.5 px-3 flex items-center gap-1.5"
                                                >
                                                    <svg wire:loading wire:target="executeAction('{{ $action }}')" class="animate-spin h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <span>{{ $actionLabels[$action] ?? ucwords(str_replace('_', ' ', $action)) }}</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>

                                {{-- Guide Callout (If cannot be resolved automatically or has instructions) --}}
                                @if(!empty($item['guide']))
                                    <div class="rounded-lg bg-slate-50 dark:bg-slate-800/80 p-3 border border-slate-200/70 dark:border-slate-700/60 text-xs">
                                        <div class="flex items-start gap-2">
                                            <svg class="h-4 w-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                                            </svg>
                                            <div class="text-slate-600 dark:text-slate-300 leading-relaxed font-sans">
                                                <strong class="font-semibold text-slate-800 dark:text-slate-200">Petunjuk Tindakan:</strong>
                                                {{ $item['guide'] }}
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Ready-to-Copy Helper Commands Section --}}
    <div class="card p-5 space-y-4" x-data="{ copiedId: null }">
        <div>
            <div class="flex items-center gap-2">
                <div class="h-7 w-7 rounded-lg bg-emerald-600 text-white flex items-center justify-center text-xs">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6.75 7.5 3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0 0 21 18V6a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 6v12a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-white">Perintah Cepat Siap Salin (Terminal & Cron)</h3>
            </div>
            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                Gunakan perintah di bawah ini untuk mengonfigurasi scheduled task di panel server (Plesk / cPanel / Linux) atau menjalankan engine via terminal.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($commands as $cmdKey => $cmd)
                <div class="rounded-xl border border-slate-200/80 dark:border-slate-800 p-4 bg-slate-50/50 dark:bg-slate-900/60 flex flex-col justify-between gap-3">
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $cmd['title'] }}</span>
                            <button
                                type="button"
                                @click="navigator.clipboard.writeText('{{ addslashes($cmd['command']) }}'); copiedId = '{{ $cmdKey }}'; setTimeout(() => copiedId = null, 2500)"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 rounded transition"
                                title="Salin perintah ke clipboard"
                            >
                                <template x-if="copiedId === '{{ $cmdKey }}'">
                                    <span class="flex items-center gap-1 text-emerald-700 dark:text-emerald-300">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                        </svg>
                                        Tersalin!
                                    </span>
                                </template>
                                <template x-if="copiedId !== '{{ $cmdKey }}'">
                                    <span class="flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                        </svg>
                                        Salin
                                    </span>
                                </template>
                            </button>
                        </div>
                        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 leading-normal">{{ $cmd['description'] }}</p>
                    </div>

                    <div class="relative">
                        <pre class="bg-slate-900 text-slate-100 rounded-lg p-2.5 text-[11px] font-mono overflow-x-auto whitespace-pre-wrap break-all select-all"><code>{{ $cmd['command'] }}</code></pre>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Action Result / Error Log Modal --}}
    @if($showActionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 dark:bg-black/80 backdrop-blur-[2px]" x-data @keydown.escape.window="$wire.closeActionModal()">
            <div class="card w-full max-w-lg shadow-2xl flex flex-col max-h-[calc(100vh-3rem)] overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-2">
                        <div class="h-6 w-6 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs font-semibold">
                            i
                        </div>
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">Output Aksi: {{ $lastActionName }}</h3>
                    </div>
                    <button wire:click="closeActionModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-5 overflow-y-auto space-y-3">
                    <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">
                        Berikut adalah respon log atau hasil eksekusi dari tindakan yang baru saja dijalankan:
                    </p>
                    <pre class="bg-slate-950 text-slate-200 p-3.5 rounded-lg text-xs font-mono overflow-x-auto whitespace-pre-wrap break-words max-h-72"><code>{{ $lastActionOutput ?: 'Tidak ada teks output dari sistem.' }}</code></pre>
                </div>

                <div class="flex items-center justify-end px-5 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40">
                    <button type="button" wire:click="closeActionModal" class="btn-secondary text-xs">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
