<?php

namespace App\Services\Sms;

use App\Contracts\SmsGateway;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HubtelSmsGateway implements SmsGateway
{
    public function __construct(private array $config)
    {
    }

    public function send(string $to, string $message): void
    {
        $clientId = $this->config['client_id'] ?? null;
        $clientSecret = $this->config['client_secret'] ?? null;

        if (! $clientId || ! $clientSecret) {
            throw new RuntimeException('Hubtel SMS credentials are not configured.');
        }

        $response = Http::timeout(10)
            ->retry(2, 250)
            ->get($this->config['base_url'], [
                'clientid' => $clientId,
                'clientsecret' => $clientSecret,
                'from' => $this->config['sender'] ?? 'Trotro',
                'to' => $to,
                'content' => $message,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Hubtel SMS failed with status '.$response->status().'.');
        }

        $status = strtolower((string) ($response->json('status') ?? ''));

        if (in_array($status, ['error', 'failed', 'failure'], true)) {
            throw new RuntimeException('Hubtel SMS was rejected: '.$response->body());
        }
    }
}
