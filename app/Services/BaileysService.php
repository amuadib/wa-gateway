<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class BaileysService
{
    protected string $baseUrl;

    protected string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) env('BAILEYS_V7_API_URL', 'http://127.0.0.1:3000'), '/');
        $this->apiKey = (string) env('BAILEYS_V7_API_KEY', '');
    }

    protected function client(int $timeout = 8)
    {
        return Http::timeout($timeout)
            ->withHeaders([
                'authorization' => 'Bearer '.$this->apiKey,
                'accept' => 'application/json',
            ]);
    }

    public function health(bool $fresh = false): array
    {
        $fetcher = function (): array {
            try {
                $res = Http::connectTimeout(1)->timeout(2)->get($this->baseUrl.'/health');
                if ($res->successful()) {
                    $json = $res->json() ?? [];

                    return [
                        'online' => true,
                        'uptime' => (int) ($json['uptime'] ?? 0),
                        'activeSessions' => (int) ($json['activeSessions'] ?? 0),
                        'connectedSessions' => (int) ($json['connectedSessions'] ?? 0),
                        'connectedIds' => $json['connectedIds'] ?? [],
                        'webhookUrl' => $json['webhookUrl'] ?? null,
                    ];
                }
            } catch (Throwable $e) {
                // Engine offline or unreachable
            }

            return [
                'online' => false,
                'uptime' => 0,
                'activeSessions' => 0,
                'connectedSessions' => 0,
                'connectedIds' => [],
                'webhookUrl' => null,
            ];
        };

        if ($fresh) {
            \Illuminate\Support\Facades\Cache::forget('baileys_engine_health');
        }

        return \Illuminate\Support\Facades\Cache::remember('baileys_engine_health', 5, $fetcher);
    }

    public function createSession(string $id): array
    {
        try {
            $res = $this->client(10)->post($this->baseUrl.'/api/sessions/create', ['id' => $id]);

            return $res->json() ?? ['status' => $res->successful(), 'message' => 'OK'];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => 'Engine Baileys tidak dapat dihubungi: '.$e->getMessage()];
        }
    }

    public function reconnectSession(string $id): array
    {
        try {
            $res = $this->client(15)->get($this->baseUrl.'/api/sessions/'.rawurlencode($id).'/reconnect');

            return $res->json() ?? ['status' => $res->successful(), 'message' => 'Reconnect diproses'];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => 'Gagal reconnect: '.$e->getMessage()];
        }
    }

    public function validateSession(string $id): array
    {
        try {
            $res = $this->client(6)->get($this->baseUrl.'/api/sessions/'.rawurlencode($id).'/validate');

            return $res->json() ?? ['status' => false, 'message' => 'Tidak ada respon'];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => 'Gagal cek status: '.$e->getMessage()];
        }
    }

    public function deleteSession(string $id): array
    {
        try {
            $res = $this->client(10)->delete($this->baseUrl.'/api/sessions/'.rawurlencode($id));

            return $res->json() ?? ['status' => $res->successful(), 'message' => 'Session dihapus'];
        } catch (Throwable $e) {
            return ['status' => false, 'message' => 'Gagal hapus di engine: '.$e->getMessage()];
        }
    }

    public function fetchGroups(string $sessionId): array
    {
        try {
            $res = $this->client(12)->get($this->baseUrl.'/api/groups/'.rawurlencode($sessionId));
            if ($res->successful()) {
                $json = $res->json() ?? [];

                return [
                    'status' => true,
                    'groups' => $json['groups'] ?? [],
                    'count' => (int) ($json['count'] ?? 0),
                ];
            }

            return [
                'status' => false,
                'groups' => [],
                'message' => $res->json('message') ?? 'Gagal memuat grup dari device.',
            ];
        } catch (Throwable $e) {
            return [
                'status' => false,
                'groups' => [],
                'message' => 'Gagal memuat grup: '.$e->getMessage(),
            ];
        }
    }

    public function testWebhook(): array
    {
        try {
            $res = $this->client(10)->get($this->baseUrl.'/api/webhook/test');

            return $res->json() ?? ['success' => $res->successful(), 'message' => 'OK'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Gagal menghubungi engine: '.$e->getMessage()];
        }
    }
}
