<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class WebhookLogs
{
    private Client $client;

    public function __construct(Client $client) { $this->client = $client; }

    /** @param array<string,mixed> $options @return mixed */
    public function list(array $options = [])
    {
        return $this->client->request('GET', '/api/v1/webhook-logs', null, $this->query($options));
    }

    /** @param array<string,mixed> $options @return mixed */
    public function stats(array $options = [])
    {
        return $this->client->request('GET', '/api/v1/webhook-logs/stats', null, $this->query($options));
    }

    /** @param array<string,mixed> $options @return array<string,mixed> */
    private function query(array $options): array
    {
        return [
            'status' => $options['status'] ?? null,
            'from_date' => $options['from_date'] ?? null,
            'to_date' => $options['to_date'] ?? null,
            'page' => $options['page'] ?? null,
            'limit' => $options['limit'] ?? null,
        ];
    }
}
