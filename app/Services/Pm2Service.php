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

        if (!is_file($this->ecosystemPath)) {
            return ['ok' => false, 'message' => 'File ecosystem.config.cjs tidak ditemukan.'];
        }

        $result = $this->runPm2(['start', $this->ecosystemPath, '--only', $target, '--update-env']);
        $this->clearHealthCache();

        if ($result['ok']) {
            $this->waitForHealth(true, 4);
            return ['ok' => true, 'message' => "Proses {$target} berhasil dijalankan."];
        }

        return ['ok' => false, 'message' => "Gagal menjalankan {$target}: " . $result['output']];
    }

    public function restart(?string $app = null): array
    {
        $target = $app ?: $this->appName;

        $result = $this->runPm2(['restart', $target, '--update-env']);

        // If process does not exist in PM2 yet, fall back to starting from ecosystem
        if (!$result['ok'] && str_contains(strtolower($result['output']), 'not found')) {
            return $this->start($target);
        }

        $this->clearHealthCache();

        if ($result['ok']) {
            $this->waitForHealth(true, 4);
            return ['ok' => true, 'message' => "Proses {$target} berhasil di-restart."];
        }

        return ['ok' => false, 'message' => "Gagal me-restart {$target}: " . $result['output']];
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

        return ['ok' => false, 'message' => "Gagal mematikan {$target}: " . $result['output']];
    }

    protected function clearHealthCache(): void
    {
        Cache::forget('baileys_engine_health');
    }

    protected function waitForHealth(bool $expectOnline, int $maxSeconds): void
    {
        $baileys = app(BaileysService::class);
        $deadline = microtime(true) + $maxSeconds;

        while (microtime(true) < $deadline) {
            usleep(400_000);
            $health = $baileys->health(fresh: true);
            if ($health['online'] === $expectOnline) {
                break;
            }
        }
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

            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());
            // Keep message concise if PM2 prints ASCII banner
            $lines = array_values(array_filter(array_map('trim', explode("\n", $output))));
            $summary = implode(' | ', array_slice($lines, -3));

            return [
                'ok' => $process->isSuccessful(),
                'output' => $summary ?: 'Exit code ' . $process->getExitCode(),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'output' => $e->getMessage(),
            ];
        }
    }

    protected function buildCommand(array $pm2Args): ?array
    {
        $isWindows = DIRECTORY_SEPARATOR === '\\';
        $nodeBin = $this->resolveNodeBin($isWindows);
        $pm2Bin = $this->resolvePm2Bin($isWindows, $nodeBin);

        if ($pm2Bin === null) {
            return null;
        }

        // Prefer invoking Node directly with PM2 JS entry point to avoid cmd.exe popups on Windows
        if ($nodeBin !== null && is_file($pm2Bin) && !str_ends_with(strtolower($pm2Bin), '.cmd')) {
            return array_merge([$nodeBin, $pm2Bin], $pm2Args);
        }

        return array_merge([$pm2Bin], $pm2Args);
    }

    protected function resolveNodeBin(bool $isWindows): ?string
    {
        $configured = trim((string) env('PM2_NODE_BIN', ''));
        if ($configured !== '' && is_file($configured)) {
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
            if (is_file($path)) {
                return $path;
            }
        }

        return $isWindows ? 'node.exe' : 'node';
    }

    protected function resolvePm2Bin(bool $isWindows, ?string $nodeBin): ?string
    {
        $configured = trim((string) env('PM2_BIN', ''));
        if ($configured !== '' && is_file($configured)) {
            return $configured;
        }

        $candidates = [];
        if ($nodeBin && is_file($nodeBin)) {
            $nodeDir = dirname($nodeBin);
            $candidates[] = $nodeDir . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'pm2' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pm2';
            $candidates[] = dirname($nodeDir) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . 'pm2' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pm2';
        }

        if ($isWindows) {
            $appData = getenv('APPDATA') ?: '';
            if ($appData !== '') {
                $candidates[] = $appData . '\\npm\\node_modules\\pm2\\bin\\pm2';
            }
            $candidates[] = 'C:\\Programs\\nvm4w\\nodejs\\node_modules\\pm2\\bin\\pm2';
        } else {
            $candidates[] = '/usr/local/bin/pm2';
            $candidates[] = '/usr/bin/pm2';
        }

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return $isWindows ? null : 'pm2';
    }

    protected function buildEnvironment(string $binary): array
    {
        $env = getenv();
        if (!is_array($env)) {
            $env = [];
        }

        $binDir = is_file($binary) ? dirname($binary) : null;
        if ($binDir) {
            $currentPath = $env['PATH'] ?? $env['Path'] ?? '';
            $env['PATH'] = $binDir . PATH_SEPARATOR . $currentPath;
        }

        $pm2Home = trim((string) env('PM2_HOME', ''));
        if ($pm2Home !== '') {
            $env['PM2_HOME'] = $pm2Home;
        }

        return $env;
    }
}
