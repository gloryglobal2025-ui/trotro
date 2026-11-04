<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ArkeselSmsGateway implements SmsGateway
{
    public function __construct(private array $config)
    {
    }

    public function send(string $to, string $message): void
    {
        $apiKey = $this->config['api_key'] ?? null;

        if (! $apiKey) {
            throw new RuntimeException('Arkesel SMS credentials are not configured.');
        }

        $response = Http::timeout(10)
            ->retry(2, 250)
            ->withHeaders(['api-key' => $apiKey])
            ->post($this->config['base_url'], [
                'sender' => $this->config['sender'] ?? 'Trotro',
                'message' => $message,
                'recipients' => [$to],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Arkesel SMS failed with status '.$response->status().'.');
        }

        $status = strtolower((string) ($response->json('status') ?? ''));

        if (in_array($status, ['error', 'failed', 'failure'], true)) {
            throw new RuntimeException('Arkesel SMS was rejected: '.$response->body());
        }
    }
}
