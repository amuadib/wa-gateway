<?php

namespace App\Services;

use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class TroubleshootService
{
    protected BaileysService $baileys;

    protected Pm2Service $pm2;

    public function __construct(BaileysService $baileys, Pm2Service $pm2)
    {
        $this->baileys = $baileys;
        $this->pm2 = $pm2;
    }

    /**
     * Run all diagnostic checks and return structured results with category groupings.
     */
    public function checkAll(): array
    {
        $results = [
            'baileys' => $this->checkBaileys(),
            'scheduler' => $this->checkScheduler(),
            'database' => $this->checkDatabase(),
            'php' => $this->checkPhp(),
            'storage' => $this->checkStorage(),
        ];

        $total = 0;
        $passed = 0;
        $warnings = 0;
        $errors = 0;

        foreach ($results as $category => $items) {
            foreach ($items as $item) {
                $total++;
                if ($item['status'] === 'ok') {
                    $passed++;
                } elseif ($item['status'] === 'warning') {
                    $warnings++;
                } else {
                    $errors++;
                }
            }
        }

        $overallStatus = ($errors > 0) ? 'error' : (($warnings > 0) ? 'warning' : 'ok');

        return [
            'overall' => [
                'status' => $overallStatus,
                'total' => $total,
                'passed' => $passed,
                'warnings' => $warnings,
                'errors' => $errors,
                'checked_at' => now()->format('Y-m-d H:i:s'),
            ],
            'categories' => $results,
            'commands' => $this->generateHelperCommands(),
        ];
    }

    /**
     * Category 1: Baileys v7 Engine, Node.js & PM2
     */
    public function checkBaileys(): array
    {
        $items = [];
        $isWindows = DIRECTORY_SEPARATOR === '\\';

        // 1. Engine HTTP Ping (port 3000)
        $health = $this->baileys->health(fresh: true);
        if ($health['online']) {
            $uptimeHours = round($health['uptime'] / 3600, 1);
            $items['engine_health'] = [
                'name' => 'Status Engine Baileys (HTTP)',
                'status' => 'ok',
                'value' => "Online (Uptime: {$uptimeHours} jam, {$health['connectedSessions']} Device Terhubung)",
                'detail' => 'Engine Node.js Baileys v7 merespons dengan baik di '.env('BAILEYS_V7_API_URL', 'http://127.0.0.1:3000'),
                'can_fix_directly' => true,
                'actions' => auth()->user()?->isAdmin() ? ['restart_engine', 'kill_engine'] : ['restart_engine'],
                'guide' => null,
            ];
        } else {
            $recentErr = $this->pm2->getRecentPm2Error(3);
            $detail = 'Engine Node.js Baileys tidak merespons di '.env('BAILEYS_V7_API_URL', 'http://127.0.0.1:3000');
            if ($recentErr) {
                $detail .= " | Error log terakhir: {$recentErr}";
            }

            $items['engine_health'] = [
                'name' => 'Status Engine Baileys (HTTP)',
                'status' => 'error',
                'value' => 'Offline / Tidak Merespons',
                'detail' => $detail,
                'can_fix_directly' => true,
                'actions' => ['start_engine', 'restart_engine'],
                'guide' => 'Klik tombol "Start Engine" di bawah. Jika PM2 belum terpasang di sistem, buka terminal/SSH lalu jalankan: pm2 start ecosystem.config.cjs --only wa-baileys',
            ];
        }

        // 2. Node.js Binary
        $nodeBin = $this->pm2->resolveNodeBin($isWindows);
        if ($nodeBin) {
            $nodeVersion = $this->executeCommandSilently([$nodeBin, '-v']);
            $items['node_bin'] = [
                'name' => 'Node.js Runtime',
                'status' => 'ok',
                'value' => 'Terdeteksi: '.($nodeVersion ?: $nodeBin),
                'detail' => "Path: {$nodeBin}",
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => null,
            ];
        } else {
            $items['node_bin'] = [
                'name' => 'Node.js Runtime',
                'status' => 'error',
                'value' => 'Tidak Ditemukan',
                'detail' => 'Binary node tidak terdeteksi di PATH sistem atau lokasi umum.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Pasang Node.js v18 atau v20. Di Plesk, pastikan Node.js Extension diaktifkan atau pasang via SSH: curl -fsSL https://deb.nodesource.com/setup_20.x | sudo bash - && sudo apt-get install -y nodejs. Atur path di .env: PM2_NODE_BIN=/usr/bin/node',
            ];
        }

        // 3. PM2 Process Manager
        $pm2Bin = $this->pm2->resolvePm2Bin($isWindows, $nodeBin);
        if ($pm2Bin) {
            $items['pm2_bin'] = [
                'name' => 'PM2 Process Manager',
                'status' => 'ok',
                'value' => 'Terdeteksi',
                'detail' => "Path: {$pm2Bin}",
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => null,
            ];
        } else {
            $items['pm2_bin'] = [
                'name' => 'PM2 Process Manager',
                'status' => 'warning',
                'value' => 'Tidak Ditemukan di PATH',
                'detail' => 'Binary pm2 tidak ditemukan. Web controller tidak dapat menjalankan start/restart engine otomatis.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Install PM2 secara global via npm: npm install -g pm2. Kemudian atur di .env: PM2_BIN=/usr/local/bin/pm2 (atau lokasi npm global Anda).',
            ];
        }

        // 4. Dependensi Baileys (node_modules)
        $baileysModulePath = base_path('baileys/node_modules/@whiskeysockets/baileys');
        if (is_dir($baileysModulePath)) {
            $items['baileys_npm'] = [
                'name' => 'Dependensi Baileys (@whiskeysockets/baileys)',
                'status' => 'ok',
                'value' => 'Terinstal',
                'detail' => 'Folder node_modules Baileys ditemukan.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => null,
            ];
        } else {
            $items['baileys_npm'] = [
                'name' => 'Dependensi Baileys (@whiskeysockets/baileys)',
                'status' => 'error',
                'value' => 'Belum Terinstal',
                'detail' => 'Dependensi npm di direktori baileys/ belum terpasang.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Buka terminal / SSH server, masuk ke folder baileys dan jalankan: cd '.base_path('baileys').' && npm install',
            ];
        }

        // 5. Direktori Sesi Baileys
        $sessionsDir = $this->resolveSessionsDir();
        if (is_dir($sessionsDir) && is_writable($sessionsDir)) {
            $items['baileys_sessions'] = [
                'name' => 'Direktori Sesi WhatsApp (Auth)',
                'status' => 'ok',
                'value' => 'Writable (Siap Pakai)',
                'detail' => "Lokasi: {$sessionsDir}",
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => null,
            ];
        } elseif (is_dir($sessionsDir)) {
            $items['baileys_sessions'] = [
                'name' => 'Direktori Sesi WhatsApp (Auth)',
                'status' => 'error',
                'value' => 'Read-Only (Tidak Bisa Menulis)',
                'detail' => "Direktori {$sessionsDir} ada tapi tidak memiliki izin tulis untuk menyimpan token QR.",
                'can_fix_directly' => true,
                'actions' => ['fix_sessions_dir'],
                'guide' => "Berikan izin tulis ke folder tersebut: chmod -R 775 {$sessionsDir}",
            ];
        } else {
            $items['baileys_sessions'] = [
                'name' => 'Direktori Sesi WhatsApp (Auth)',
                'status' => 'warning',
                'value' => 'Belum Ada',
                'detail' => "Direktori {$sessionsDir} belum dibuat.",
                'can_fix_directly' => true,
                'actions' => ['fix_sessions_dir'],
                'guide' => 'Klik tombol "Buat Direktori Sesi" atau buat manual folder tersebut.',
            ];
        }

        // 6. Webhook URL Configuration
        $webhookUrl = (string) env('BAILEYS_V7_WEBHOOK_URL', '');
        $appUrl = (string) env('APP_URL', '');
        if (! empty($webhookUrl)) {
            $isLocalMismatch = str_contains($webhookUrl, 'wa-gateway.local') && ! str_contains($appUrl, 'wa-gateway.local');
            if ($isLocalMismatch) {
                $items['webhook_url'] = [
                    'name' => 'Konfigurasi Webhook Baileys',
                    'status' => 'warning',
                    'value' => $webhookUrl,
                    'detail' => 'Nilai webhook masih menggunakan URL contoh (wa-gateway.local), sementara APP_URL adalah '.$appUrl,
                    'can_fix_directly' => false,
                    'actions' => [],
                    'guide' => 'Sesuaikan variabel BAILEYS_V7_WEBHOOK_URL di file .env menjadi: '.rtrim($appUrl, '/').'/webhook/baileys-v7',
                ];
            } else {
                $items['webhook_url'] = [
                    'name' => 'Konfigurasi Webhook Baileys',
                    'status' => 'ok',
                    'value' => $webhookUrl,
                    'detail' => 'Webhook URL terkonfigurasi dengan benar.',
                    'can_fix_directly' => false,
                    'actions' => [],
                    'guide' => null,
                ];
            }
        } else {
            $items['webhook_url'] = [
                'name' => 'Konfigurasi Webhook Baileys',
                'status' => 'error',
                'value' => 'Kosong / Belum Diatur',
                'detail' => 'Variabel BAILEYS_V7_WEBHOOK_URL belum diatur di .env.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Tambahkan di file .env: BAILEYS_V7_WEBHOOK_URL="'.rtrim($appUrl, '/').'/webhook/baileys-v7"',
            ];
        }

        return $items;
    }

    /**
     * Category 2: Laravel Scheduler & Queue
     */
    public function checkScheduler(): array
    {
        $items = [];

        // 1. Scheduler Heartbeat (Cron detection)
        $heartbeat = Cache::get('scheduler:last_heartbeat');
        if ($heartbeat) {
            $lastRun = Carbon::parse($heartbeat);
            $diffSeconds = (int) abs(now()->diffInSeconds($lastRun));
            $diffMinutes = (int) round($diffSeconds / 60);

            if ($diffSeconds <= 180) { // < 3 minutes
                $items['scheduler_cron'] = [
                    'name' => 'Status Scheduler (Cron Job)',
                    'status' => 'ok',
                    'value' => "Aktif (Terakhir berjalan {$diffSeconds} detik lalu)",
                    'detail' => 'Perintah php artisan schedule:run berjalan rutin setiap menit sesuai jadwal.',
                    'can_fix_directly' => true,
                    'actions' => ['run_scheduler_once'],
                    'guide' => null,
                ];
            } elseif ($diffMinutes <= 10) {
                $items['scheduler_cron'] = [
                    'name' => 'Status Scheduler (Cron Job)',
                    'status' => 'warning',
                    'value' => "Terlambat (Terakhir berjalan {$diffMinutes} menit lalu)",
                    'detail' => 'Cron job terdeteksi pernah berjalan, namun lebih dari 3 menit yang lalu.',
                    'can_fix_directly' => true,
                    'actions' => ['run_scheduler_once'],
                    'guide' => 'Pastikan entri cron diatur berjalan setiap menit (* * * * *) tanpa jeda panjang.',
                ];
            } else {
                $items['scheduler_cron'] = [
                    'name' => 'Status Scheduler (Cron Job)',
                    'status' => 'error',
                    'value' => "Mati / Terhenti (Terakhir: {$lastRun->format('Y-m-d H:i')})",
                    'detail' => 'Scheduler tidak berjalan lebih dari 10 menit. Antrian pesan otomatis tidak akan terkirim.',
                    'can_fix_directly' => true,
                    'actions' => ['run_scheduler_once'],
                    'guide' => 'Tambahkan perintah cron di Plesk / Server Anda (lihat petunjuk perintah di bagian bawah).',
                ];
            }
        } else {
            $items['scheduler_cron'] = [
                'name' => 'Status Scheduler (Cron Job)',
                'status' => 'error',
                'value' => 'Belum Pernah Berjalan',
                'detail' => 'Belum ada deteksi cron php artisan schedule:run yang mengeksekusi scheduler Laravel.',
                'can_fix_directly' => true,
                'actions' => ['run_scheduler_once'],
                'guide' => 'Jadwalkan perintah cron di server atau gunakan tombol "Jalankan Scheduler Sekali" untuk menguji.',
            ];
        }

        // 2. Queue Message Status & Last Queue Run
        $queueLastRun = Cache::get('message_queue:last_run');
        $pendingCount = Message::where('sent', 0)->count();
        $failedCount = Message::where('sent', 99)->orWhere('status', 'FAILED')->count();

        $queueValue = ($queueLastRun)
            ? 'Terakhir diproses: '.Carbon::parse($queueLastRun)->diffForHumans()
            : 'Belum ada riwayat proses';

        $queueDetail = "Pesan antrian pending: {$pendingCount} pesan | Pesan gagal: {$failedCount} pesan";

        $items['message_queue'] = [
            'name' => 'Antrian Pesan (message:send-to-api)',
            'status' => ($pendingCount > 100) ? 'warning' : 'ok',
            'value' => $queueValue,
            'detail' => $queueDetail,
            'can_fix_directly' => true,
            'actions' => ['run_queue_once'],
            'guide' => ($pendingCount > 0)
                ? 'Ada '.$pendingCount.' pesan menunggu dikirim. Klik "Proses Antrian Sekali" atau pastikan scheduler/cron aktif.'
                : null,
        ];

        return $items;
    }

    /**
     * Category 3: Database & Migrations
     */
    public function checkDatabase(): array
    {
        $items = [];

        // 1. DB Connection
        try {
            $pdo = DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $dbName = DB::connection()->getDatabaseName();
            $version = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

            $items['db_connection'] = [
                'name' => 'Koneksi Database ('.strtoupper($driver).')',
                'status' => 'ok',
                'value' => "Terhubung ({$dbName})",
                'detail' => "Server version: {$version} di host ".env('DB_HOST', '127.0.0.1').':'.env('DB_PORT', '3306'),
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => null,
            ];

            // 2. Migrations Status
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles($migrator->paths());
            $ran = $migrator->getRepository()->getRan();
            $pending = array_diff(array_keys($files), $ran);

            if (empty($pending)) {
                $items['db_migrations'] = [
                    'name' => 'Tabel & Migrasi Database',
                    'status' => 'ok',
                    'value' => 'Semua Migrasi Sudah Dijalankan ('.count($ran).' migrasi)',
                    'detail' => 'Skema database up to date.',
                    'can_fix_directly' => false,
                    'actions' => [],
                    'guide' => null,
                ];
            } else {
                $items['db_migrations'] = [
                    'name' => 'Tabel & Migrasi Database',
                    'status' => 'error',
                    'value' => count($pending).' Migrasi Pending',
                    'detail' => 'Daftar migrasi belum jalan: '.implode(', ', array_slice($pending, 0, 3)),
                    'can_fix_directly' => true,
                    'actions' => ['run_migrations'],
                    'guide' => 'Klik tombol "Jalankan Migrasi" di bawah atau jalankan php artisan migrate --force di terminal.',
                ];
            }
        } catch (Throwable $e) {
            $items['db_connection'] = [
                'name' => 'Koneksi Database',
                'status' => 'error',
                'value' => 'Gagal Terhubung',
                'detail' => 'Error: '.$e->getMessage(),
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Periksa apakah layanan MySQL menyala dan periksa konfigurasi DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD pada file .env.',
            ];

            $items['db_migrations'] = [
                'name' => 'Tabel & Migrasi Database',
                'status' => 'warning',
                'value' => 'Tidak Dapat Diperiksa',
                'detail' => 'Pemeriksaan migrasi memerlukan koneksi database yang aktif.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Perbaiki koneksi database terlebih dahulu.',
            ];
        }

        return $items;
    }

    /**
     * Category 4: PHP & Server Environment
     */
    public function checkPhp(): array
    {
        $items = [];

        // 1. PHP Version
        $phpVersion = PHP_VERSION;
        $isPhpOk = version_compare($phpVersion, '8.1.0', '>=');
        $items['php_version'] = [
            'name' => 'Versi PHP',
            'status' => $isPhpOk ? 'ok' : 'error',
            'value' => "PHP {$phpVersion}",
            'detail' => $isPhpOk ? 'Sesuai requirement Laravel 10 (PHP >= 8.1).' : 'Laravel 10 membutuhkan minimal PHP 8.1.',
            'can_fix_directly' => false,
            'actions' => [],
            'guide' => $isPhpOk ? null : 'Upgrade versi PHP hosting / Plesk Anda ke minimal PHP 8.1 (disarankan PHP 8.2).',
        ];

        // 2. Required PHP Extensions
        $requiredExtensions = [
            'pdo_mysql' => 'Koneksi Database MySQL',
            'curl' => 'Koneksi HTTP & API Baileys',
            'openssl' => 'Enkripsi & HTTPS Webhook',
            'mbstring' => 'Pemrosesan Teks Pesan WhatsApp',
            'fileinfo' => 'MIME Type Dokumen & Gambar Media',
            'json' => 'Format Payload Webhook & API',
            'tokenizer' => 'Kompilasi Blade & Framework',
            'xml' => 'XML Parser',
        ];

        $missingExt = [];
        foreach ($requiredExtensions as $ext => $purpose) {
            if (! extension_loaded($ext)) {
                $missingExt[] = "{$ext} ({$purpose})";
            }
        }

        if (empty($missingExt)) {
            $items['php_extensions'] = [
                'name' => 'Ekstensi PHP Inti',
                'status' => 'ok',
                'value' => 'Lengkap ('.count($requiredExtensions).' ekstensi aktif)',
                'detail' => 'Semua ekstensi PHP wajib (pdo_mysql, curl, mbstring, openssl, fileinfo, dll) telah terpasang.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => null,
            ];
        } else {
            $items['php_extensions'] = [
                'name' => 'Ekstensi PHP Inti',
                'status' => 'error',
                'value' => count($missingExt).' Ekstensi Hilang',
                'detail' => 'Ekstensi belum aktif: '.implode(', ', $missingExt),
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Aktifkan ekstensi tersebut di panel hosting (Plesk -> PHP Settings -> Extensions) atau install via terminal: apt-get install php8.2-<nama-ekstensi>.',
            ];
        }

        // 3. open_basedir Setting
        $openBasedir = trim((string) ini_get('open_basedir'));
        if (empty($openBasedir) || $openBasedir === 'none') {
            $items['open_basedir'] = [
                'name' => 'PHP open_basedir Restriction',
                'status' => 'ok',
                'value' => 'Tidak Dibatasi (None)',
                'detail' => 'PHP dapat mengakses binary node dan file proyek tanpa pembatasan direktori ketat.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => null,
            ];
        } else {
            $baseAllowed = $this->pm2->isAllowedByOpenBasedir(base_path());
            $items['open_basedir'] = [
                'name' => 'PHP open_basedir Restriction',
                'status' => $baseAllowed ? 'ok' : 'warning',
                'value' => 'Aktif',
                'detail' => 'open_basedir aktif: '.substr($openBasedir, 0, 80).(strlen($openBasedir) > 80 ? '...' : ''),
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Jika terjadi error open_basedir restriction saat start PM2 atau akses storage, tambahkan path proyek ('.base_path().') dan folder Node.js ke nilai open_basedir di Plesk PHP Settings.',
            ];
        }

        // 4. disable_functions Setting
        $disableFunctions = array_filter(array_map('trim', explode(',', (string) ini_get('disable_functions'))));
        $neededFunctions = ['proc_open', 'exec', 'shell_exec'];
        $blockedNeeded = array_intersect($neededFunctions, $disableFunctions);

        if (empty($blockedNeeded)) {
            $items['disable_functions'] = [
                'name' => 'Fungsi Eksekusi Sistem (Shell / Process)',
                'status' => 'ok',
                'value' => 'Tersedia (proc_open & exec aktif)',
                'detail' => 'Web controller dapat mengelola proses PM2 secara langsung.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => null,
            ];
        } else {
            $items['disable_functions'] = [
                'name' => 'Fungsi Eksekusi Sistem (Shell / Process)',
                'status' => 'warning',
                'value' => 'Dibatasi: '.implode(', ', $blockedNeeded),
                'detail' => 'Fungsi eksekusi diblokir oleh server. Start/Restart PM2 dari browser akan gagal.',
                'can_fix_directly' => false,
                'actions' => [],
                'guide' => 'Hapus '.implode(', ', $blockedNeeded).' dari baris disable_functions di php.ini Plesk agar fitur Start/Restart engine dari browser dapat bekerja, atau kelola proses PM2 via terminal SSH.',
            ];
        }

        // 5. Memory Limit & Max Execution Time
        $memLimit = ini_get('memory_limit') ?: '128M';
        $maxTime = ini_get('max_execution_time') ?: '30';
        $items['php_limits'] = [
            'name' => 'Alokasi Memori & Timeout PHP',
            'status' => 'ok',
            'value' => "Memory Limit: {$memLimit} | Timeout: {$maxTime}s",
            'detail' => 'Konfigurasi runtime PHP normal.',
            'can_fix_directly' => false,
            'actions' => [],
            'guide' => null,
        ];

        return $items;
    }

    /**
     * Category 5: Storage & Cache Writable Permissions
     */
    public function checkStorage(): array
    {
        $items = [];

        // 1. Storage Logs
        $logsPath = storage_path('logs');
        $isLogsWritable = is_dir($logsPath) && is_writable($logsPath);
        $items['storage_logs'] = [
            'name' => 'Direktori storage/logs',
            'status' => $isLogsWritable ? 'ok' : 'error',
            'value' => $isLogsWritable ? 'Writable' : 'Tidak Dapat Menulis',
            'detail' => "Lokasi: {$logsPath}",
            'can_fix_directly' => true,
            'actions' => ['test_storage_write'],
            'guide' => $isLogsWritable ? null : 'Berikan izin tulis: chmod -R 775 '.$logsPath.' && chown -R www-data:www-data '.$logsPath,
        ];

        // 2. Storage Framework (Views, Sessions, Cache)
        $frameworkPath = storage_path('framework');
        $isFwWritable = is_dir($frameworkPath) && is_writable($frameworkPath);
        $items['storage_framework'] = [
            'name' => 'Direktori storage/framework (Cache & Views)',
            'status' => $isFwWritable ? 'ok' : 'error',
            'value' => $isFwWritable ? 'Writable' : 'Tidak Dapat Menulis',
            'detail' => "Lokasi: {$frameworkPath}",
            'can_fix_directly' => true,
            'actions' => ['clear_cache'],
            'guide' => $isFwWritable ? null : 'Berikan izin tulis: chmod -R 775 '.$frameworkPath,
        ];

        // 3. Bootstrap Cache
        $bCachePath = base_path('bootstrap/cache');
        $isBcacheWritable = is_dir($bCachePath) && is_writable($bCachePath);
        $items['bootstrap_cache'] = [
            'name' => 'Direktori bootstrap/cache',
            'status' => $isBcacheWritable ? 'ok' : 'error',
            'value' => $isBcacheWritable ? 'Writable' : 'Tidak Dapat Menulis',
            'detail' => "Lokasi: {$bCachePath}",
            'can_fix_directly' => true,
            'actions' => ['clear_cache'],
            'guide' => $isBcacheWritable ? null : 'Berikan izin tulis: chmod -R 775 '.$bCachePath,
        ];

        // 4. APP_KEY
        $appKey = env('APP_KEY');
        $hasKey = ! empty($appKey) && str_starts_with($appKey, 'base64:');
        $items['app_key'] = [
            'name' => 'Kunci Enkripsi Aplikasi (APP_KEY)',
            'status' => $hasKey ? 'ok' : 'error',
            'value' => $hasKey ? 'Terkonfigurasi' : 'Kosong / Tidak Valid',
            'detail' => $hasKey ? 'APP_KEY valid.' : 'APP_KEY kosong di .env. Sesi dan enkripsi tidak akan bekerja.',
            'can_fix_directly' => true,
            'actions' => ['generate_app_key'],
            'guide' => $hasKey ? null : 'Jalankan php artisan key:generate di terminal atau klik tombol aksi.',
        ];

        // 5. Storage Public Media & Symlink
        $mediaStoragePath = storage_path('app/public/whatsapp_media');
        if (! is_dir($mediaStoragePath)) {
            @mkdir($mediaStoragePath, 0775, true);
        }
        $isMediaWritable = is_dir($mediaStoragePath) && is_writable($mediaStoragePath);
        $publicLinkPath = public_path('storage');
        $hasLink = file_exists($publicLinkPath);

        $mediaStatus = ($isMediaWritable && $hasLink) ? 'ok' : ($isMediaWritable ? 'warning' : 'error');
        $items['storage_media'] = [
            'name' => 'Penyimpanan Media WhatsApp (storage/app/public)',
            'status' => $mediaStatus,
            'value' => ($isMediaWritable && $hasLink) ? 'Siap & Terhubung' : (! $hasLink ? 'Symlink Belum Dibuat' : 'Tidak Dapat Menulis'),
            'detail' => $hasLink ? "Storage publik siap menampung foto/dokumen ({$mediaStoragePath})." : 'Jalankan php artisan storage:link agar foto dapat diakses via web.',
            'can_fix_directly' => ! $hasLink,
            'actions' => $hasLink ? [] : ['create_storage_link'],
            'guide' => $hasLink ? null : 'Jalankan php artisan storage:link di terminal server atau klik tombol aksi.',
        ];

        return $items;
    }

    /**
     * Generate ready-to-copy commands for different operating systems & hosting panels (Plesk/Linux/Windows).
     */
    public function generateHelperCommands(): array
    {
        $phpBin = $this->resolvePhpBinary();
        $base = base_path();
        $artisan = base_path('artisan');

        return [
            'plesk_cron' => [
                'title' => 'Plesk Panel (Scheduled Tasks / Cron)',
                'description' => 'Buka Plesk -> Websites & Domains -> Scheduled Tasks -> Add Task. Pilih "Run a command", atur Run: Every minute (* * * * *), lalu tempel:',
                'command' => "cd {$base} && {$phpBin} artisan schedule:run >> /dev/null 2>&1",
            ],
            'linux_cron' => [
                'title' => 'Linux / Ubuntu Crontab (via SSH)',
                'description' => 'Jalankan perintah crontab -e lalu tambahkan baris berikut di paling bawah:',
                'command' => "* * * * * cd {$base} && {$phpBin} artisan schedule:run >> /dev/null 2>&1",
            ],
            'pm2_start_engine' => [
                'title' => 'PM2 Start Baileys Engine (Terminal/SSH)',
                'description' => 'Jalankan dari root folder proyek jika engine offline:',
                'command' => 'pm2 start ecosystem.config.cjs --only wa-baileys',
            ],
            'pm2_start_scheduler' => [
                'title' => 'PM2 Alternatif Scheduler (Tanpa Cron Linux/Windows)',
                'description' => 'Jika cron server diblokir, PM2 scheduler siap pakai menjalankan antrian setiap menit:',
                'command' => 'pm2 start ecosystem.config.cjs --only wa-scheduler',
            ],
            'windows_schtasks' => [
                'title' => 'Windows Task Scheduler (CMD Administrator)',
                'description' => 'Jika menjalankan di server Windows lokal / Laragon:',
                'command' => "schtasks /create /tn \"WAGatewayScheduler\" /tr \"\\\"{$phpBin}\\\" \\\"{$artisan}\\\" schedule:run\" /sc minute /mo 1 /f",
            ],
            'npm_install' => [
                'title' => 'Install Paket Baileys v7',
                'description' => 'Jalankan jika node_modules di folder baileys belum lengkap:',
                'command' => 'cd '.base_path('baileys').' && npm install',
            ],
        ];
    }

    /**
     * Direct Action: Run Migrations
     */
    public function runMigrations(): array
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());

            return [
                'ok' => true,
                'message' => 'Migrasi database berhasil dijalankan.',
                'output' => $output ?: 'Database migration completed.',
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal menjalankan migrasi: '.$e->getMessage(),
                'output' => $e->getTraceAsString(),
            ];
        }
    }

    /**
     * Direct Action: Clear Application & Config Caches
     */
    public function clearCache(): array
    {
        try {
            Artisan::call('optimize:clear');
            $output = trim(Artisan::output());
            Cache::flush();

            return [
                'ok' => true,
                'message' => 'Semua cache (config, route, view, cache file) berhasil dibersihkan.',
                'output' => $output ?: 'Cache cleared successfully.',
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal membersihkan cache: '.$e->getMessage(),
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Direct Action: Trigger Scheduler Once
     */
    public function runSchedulerOnce(): array
    {
        try {
            Artisan::call('schedule:run');
            $output = trim(Artisan::output());

            // Update heartbeat timestamp immediately
            Cache::put('scheduler:last_heartbeat', now()->toIso8601String(), 86400);

            return [
                'ok' => true,
                'message' => 'Perintah schedule:run berhasil dieksekusi. Heartbeat diperbarui.',
                'output' => $output ?: 'No scheduled commands were ready to run, but scheduler executed successfully.',
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal menjalankan scheduler: '.$e->getMessage(),
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Direct Action: Trigger Send Queue Once
     */
    public function runQueueOnce(): array
    {
        try {
            Cache::put('message_queue:last_run', now()->toIso8601String(), 86400);
            Artisan::call('message:send-to-api');
            $output = trim(Artisan::output());

            return [
                'ok' => true,
                'message' => 'Pemeriksaan antrian pesan berhasil dijalankan.',
                'output' => $output ?: 'Tidak ada pesan pending dalam antrian.',
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal memproses antrian: '.$e->getMessage(),
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Direct Action: Start Engine via PM2
     */
    public function startEngine(): array
    {
        return $this->pm2->start();
    }

    /**
     * Direct Action: Restart Engine via PM2
     */
    public function restartEngine(): array
    {
        return $this->pm2->restart();
    }

    /**
     * Direct Action: Kill Engine via PM2
     */
    public function killEngine(): array
    {
        return $this->pm2->kill();
    }

    /**
     * Direct Action: Ensure Sessions Directory Exists
     */
    public function fixSessionsDir(): array
    {
        $dir = $this->resolveSessionsDir();
        try {
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            @chmod($dir, 0775);

            return [
                'ok' => true,
                'message' => "Direktori sesi berhasil dibuat/diperbaiki di: {$dir}",
                'output' => $dir,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal membuat direktori sesi: '.$e->getMessage(),
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Direct Action: Test Storage Writable by Creating a Temp File
     */
    public function testStorageWrite(): array
    {
        $testFile = storage_path('logs/test_write_'.time().'.tmp');
        try {
            file_put_contents($testFile, 'write_test_ok');
            $read = @file_get_contents($testFile);
            @unlink($testFile);

            if ($read === 'write_test_ok') {
                return [
                    'ok' => true,
                    'message' => 'Uji tulis dan hapus file di storage/logs berhasil.',
                    'output' => 'File permission storage/logs valid.',
                ];
            }

            return [
                'ok' => false,
                'message' => 'File berhasil dibuat namun isi tidak sesuai.',
                'output' => '',
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Uji tulis gagal: '.$e->getMessage(),
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Direct Action: Generate APP_KEY if empty
     */
    public function generateAppKey(): array
    {
        try {
            Artisan::call('key:generate', ['--force' => true]);
            $output = trim(Artisan::output());

            return [
                'ok' => true,
                'message' => 'APP_KEY berhasil di-generate ke file .env.',
                'output' => $output,
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal generate key: '.$e->getMessage(),
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Direct Action: Create storage symlink (php artisan storage:link)
     */
    public function createStorageLink(): array
    {
        try {
            Artisan::call('storage:link');
            $output = trim(Artisan::output());

            return [
                'ok' => true,
                'message' => 'Symlink storage publik berhasil dibuat.',
                'output' => $output ?: 'The [public/storage] link has been connected to [storage/app/public].',
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Gagal membuat symlink storage: '.$e->getMessage(),
                'output' => $e->getMessage(),
            ];
        }
    }

    /**
     * Resolve actual sessions directory on disk.
     */
    public function resolveSessionsDir(): string
    {
        $configured = env('BAILEYS_V7_SESSIONS_DIR');
        if ($configured) {
            if (str_starts_with($configured, '.')) {
                return base_path('baileys/'.$configured);
            }

            return $configured;
        }

        return storage_path('app/baileys-sessions');
    }

    /**
     * Resolve the absolute path to current PHP CLI binary.
     */
    public function resolvePhpBinary(): string
    {
        if (defined('PHP_BINARY') && PHP_BINARY) {
            return PHP_BINARY;
        }

        return (DIRECTORY_SEPARATOR === '\\') ? 'php.exe' : 'php';
    }

    /**
     * Execute a simple CLI command silently and return its stdout.
     */
    protected function executeCommandSilently(array $command): ?string
    {
        if (! function_exists('proc_open')) {
            return null;
        }

        try {
            $process = new \Symfony\Component\Process\Process($command);
            $process->setTimeout(3);
            $process->run();

            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }

            return null;
        } catch (Throwable) {
            return null;
        }
    }
}
