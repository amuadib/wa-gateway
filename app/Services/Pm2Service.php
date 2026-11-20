<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Throwable;

class Pm2Service
{
    protected string $appName;

    protected string $ecosystemPath;

    public function __construct()
    {
        $this->appName = (string) env('PM2_APP_NAME', 'wa-baileys');
        $this->ecosystemPath = base_path('ecosystem.config.cjs');
    }

    public function start(?string $app = null): array
    {
        $target = $app ?: $this->appName;

        if (! $this->safeIsFile($this->ecosystemPath)) {
            return ['ok' => false, 'message' => 'File ecosystem.config.cjs tidak ditemukan.'];
        }

        $result = $this->runPm2(['start', $this->ecosystemPath, '--only', $target, '--update-env']);
        $this->clearHealthCache();

        if ($result['ok']) {
            $isHealthy = $this->waitForHealth(true, 4);
            if ($isHealthy) {
                return ['ok' => true, 'message' => "Proses {$target} berhasil dijalankan."];
            }

            $recentError = $this->getRecentPm2Error();
            $url = (string) env('BAILEYS_V7_API_URL', 'http://127.0.0.1:3000');
            $msg = "Perintah start PM2 terkirim, namun engine belum merespons di {$url}.";
            if ($recentError) {
                $msg .= " Error log: {$recentError}";
            }

            return ['ok' => false, 'message' => $msg];
        }

        return ['ok' => false, 'message' => "Gagal menjalankan {$target}: ".$result['output']];
    }

    public function restart(?string $app = null): array
    {
        $target = $app ?: $this->appName;

        $result = $this->runPm2(['restart', $target, '--update-env']);

        // If process does not exist in PM2 yet, fall back to starting from ecosystem
        if (! $result['ok'] && str_contains(strtolower($result['output']), 'not found')) {
            return $this->start($target);
        }

        $this->clearHealthCache();

        if ($result['ok']) {
            $isHealthy = $this->waitForHealth(true, 4);
            if ($isHealthy) {
                return ['ok' => true, 'message' => "Proses {$target} berhasil di-restart."];
            }

            $recentError = $this->getRecentPm2Error();
            $url = (string) env('BAILEYS_V7_API_URL', 'http://127.0.0.1:3000');
            $msg = "Perintah restart PM2 terkirim, namun engine belum merespons di {$url}.";
            if ($recentError) {
                $msg .= " Error log: {$recentError}";
            }

            return ['ok' => false, 'message' => $msg];
        }

        return ['ok' => false, 'message' => "Gagal me-restart {$target}: ".$result['output']];
    }

    public function kill(?string $app = null): array
    {
        $target = $app ?: $this->appName;

        $result = $this->runPm2(['delete', $target]);
        $this->clearHealthCache();

        if ($result['ok']) {
            $this->waitForHealth(false, 3);

            return ['ok' => true, 'message' => "Proses {$target} berhasil dimatikan (killed)."];
        }

        if (str_contains(strtolower($result['output']), 'not found')) {
            return ['ok' => true, 'message' => "Proses {$target} sudah dalam keadaan mati."];
        }

        return ['ok' => false, 'message' => "Gagal mematikan {$target}: ".$result['output']];
    }

    public function getRecentPm2Error(int $lines = 4): ?string
    {
        $logFile = storage_path('logs/pm2-baileys-error.log');
        if (! $this->safeIsFile($logFile)) {
            return null;
        }

        try {
            $content = @file_get_contents($logFile);
            if (! $content) {
                return null;
            }
            $allLines = array_values(array_filter(array_map('trim', explode("\n", $content))));
            if (empty($allLines)) {
                return null;
            }
            $slice = array_slice($allLines, -$lines);

            return implode("\n", $slice);
        } catch (Throwable) {
            return null;
        }
    }

    protected function clearHealthCache(): void
    {
        Cache::forget('baileys_engine_health');
    }

    protected function waitForHealth(bool $expectOnline, int $maxSeconds): bool
    {
        $baileys = app(BaileysService::class);
        $deadline = microtime(true) + $maxSeconds;

        while (microtime(true) < $deadline) {
            usleep(400_000);
            $health = $baileys->health(fresh: true);
            if ($health['online'] === $expectOnline) {
                return true;
            }
        }

        return false;
    }

    protected function runPm2(array $args): array
    {
        $command = $this->buildCommand($args);
        if ($command === null) {
            return [
                'ok' => false,
                'output' => 'Binary Node.js atau PM2 tidak ditemukan. Atur PM2_NODE_BIN & PM2_BIN di .env.',
            ];
        }

        try {
            $env = $this->buildEnvironment($command[0]);
            $process = new Process($command, base_path(), $env, null, 25);
            $process->run();

            $output = trim($process->getOutput()."\n".$process->getErrorOutput());
            // Keep message concise if PM2 prints ASCII banner
            $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));
            $summary = implode(' | ', array_slice($lines, -3));

            return [
                'ok' => $process->isSuccessful(),
                'output' => $summary ?: 'Exit code '.$process->getExitCode(),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'output' => $e->getMessage(),
            ];
        }
    }

    public function buildCommand(array $pm2Args): ?array
    {
        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $nodeBin = $this->resolveNodeBin($isWindows);
        $pm2Bin = $this->resolvePm2Bin($isWindows, $nodeBin);

        if ($pm2Bin === null) {
            return null;
        }

        // Prefer invoking Node directly with PM2 JS entry point to avoid cmd.exe popups on Windows
        if ($nodeBin !== null && $this->isJsScript($pm2Bin)) {
            return array_merge([$nodeBin, $pm2Bin], $pm2Args);
        }

        return array_merge([$pm2Bin], $pm2Args);
    }

    public function resolveNodeBin(bool $isWindows): ?string
    {
        $configured = trim((string) env('PM2_NODE_BIN', ''));
        if ($configured !== '' && $this->isValidOrUntestablePath($configured)) {
            return $configured;
        }

        $candidates = $isWindows
            ? [
                'C:\\Programs\\nvm4w\\nodejs\\node.exe',
                'C:\\Program Files\\nodejs\\node.exe',
                'C:\\Program Files (x86)\\nodejs\\node.exe',
            ]
            : [
                '/usr/bin/node',
                '/usr/local/bin/node',
                '/opt/plesk/node/22/bin/node',
                '/opt/plesk/node/20/bin/node',
            ];

        foreach ($candidates as $path) {
            if ($this->safeIsFile($path)) {
                return $path;
            }
        }

        return $isWindows ? 'node.exe' : 'node';
    }

    public function resolvePm2Bin(bool $isWindows, ?string $nodeBin): ?string
    {
        $configured = trim((string) env('PM2_BIN', ''));
        if ($configured !== '' && $this->isValidOrUntestablePath($configured)) {
            return $configured;
        }

        $candidates = [];
        if ($nodeBin && $this->safeIsFile($nodeBin)) {
            $nodeDir = dirname($nodeBin);
            $candidates[] = $nodeDir.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'pm2'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pm2';
            $candidates[] = dirname($nodeDir).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR.'pm2'.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'pm2';
        }

        if ($isWindows) {
            $appData = getenv('APPDATA') ?: '';
            if ($appData !== '') {
                $candidates[] = $appData.'\\npm\\node_modules\\pm2\\bin\\pm2';
            }
            $candidates[] = 'C:\\Programs\\nvm4w\\nodejs\\node_modules\\pm2\\bin\\pm2';
        } else {
            $candidates[] = '/usr/local/bin/pm2';
            $candidates[] = '/usr/bin/pm2';
        }

        foreach ($candidates as $path) {
            if ($this->safeIsFile($path)) {
                return $path;
            }
        }

        return 'pm2';
    }

    protected function buildEnvironment(string $binary): array
    {
        $env = getenv();
        if (! is_array($env)) {
            $env = [];
        }

        $hasSeparator = str_contains($binary, '/') || str_contains($binary, '\\');
        $binDir = $hasSeparator ? dirname($binary) : null;

        if ($binDir) {
            $currentPath = $env['PATH'] ?? $env['Path'] ?? '';
            $env['PATH'] = $binDir.PATH_SEPARATOR.$currentPath;
        }

        $pm2Home = trim((string) env('PM2_HOME', ''));
        if ($pm2Home !== '') {
            $env['PM2_HOME'] = $pm2Home;
        }

        return $env;
    }

    /**
     * Determine if a string path points to a JavaScript entrypoint (e.g. PM2 bin JS) rather than a binary / shell wrapper.
     */
    protected function isJsScript(string $path): bool
    {
        $lower = strtolower($path);
        if (str_ends_with($lower, '.cmd') || str_ends_with($lower, '.bat') || str_ends_with($lower, '.exe')) {
            return false;
        }

        return str_contains($path, '/') || str_contains($path, '\\') || str_ends_with($lower, '.js');
    }

    /**
     * Check if a path is valid, or if open_basedir prevents checking external paths, trust explicitly configured values.
     */
    protected function isValidOrUntestablePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        // If it is just a command name (e.g. "node", "pm2"), rely on system PATH
        if (! str_contains($path, '/') && ! str_contains($path, '\\')) {
            return true;
        }

        // If open_basedir restriction is active and the path is outside the allowed directories,
        // PHP cannot run filesystem checks on it, so we trust the explicitly configured value.
        if ($this->isOpenBasedirActive() && ! $this->isAllowedByOpenBasedir($path)) {
            return true;
        }

        return $this->safeIsFile($path);
    }

    /**
     * Determine if open_basedir is active in current PHP environment.
     */
    public function isOpenBasedirActive(): bool
    {
        return trim((string) ini_get('open_basedir')) !== '';
    }

    /**
     * Check whether a path falls within the PHP open_basedir allowed paths.
     */
    public function isAllowedByOpenBasedir(string $path): bool
    {
        $openBasedir = trim((string) ini_get('open_basedir'));
        if ($openBasedir === '') {
            return true;
        }

        $allowedDirs = array_filter(explode(PATH_SEPARATOR, $openBasedir));
        if (empty($allowedDirs)) {
            return true;
        }

        $normalizedPath = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        foreach ($allowedDirs as $allowed) {
            $allowed = rtrim(str_replace(['\\', '/'], DIRECTORY_SEPARATOR, trim($allowed)), DIRECTORY_SEPARATOR);
            if ($allowed === '') {
                continue;
            }

            if (DIRECTORY_SEPARATOR === '\\') {
                if (stripos($normalizedPath, $allowed) === 0) {
                    return true;
                }
            } else {
                if (str_starts_with($normalizedPath, $allowed)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Safely check if a file exists without triggering open_basedir restriction warnings.
     */
    public function safeIsFile(?string $path): bool
    {
        if (! $path || ! is_string($path)) {
            return false;
        }

        if (! $this->isAllowedByOpenBasedir($path)) {
            return false;
        }

        return @is_file($path);
    }
}
