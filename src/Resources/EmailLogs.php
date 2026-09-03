<?php

declare(strict_types=1);

namespace MonaPay\Resources;

use MonaPay\Client;

final class EmailLogs
{
    private Client $client;
    public function __construct(Client $client) { $this->client = $client; }

    /** @param array<string,mixed> $options @return mixed */
    public function list(array $options = []) { return $this->client->request('GET', '/api/v1/email-logs', null, $this->query($options)); }
    /** @param array<string,mixed> $options @return mixed */
    public function stats(array $options = [])
    {
        return $this->client->request('GET', '/api/v1/email-logs/stats', null, [
            'from_date' => $options['from_date'] ?? null, 'to_date' => $options['to_date'] ?? null,
        ]);
    }
    /** @param array<string,mixed> $options @return array<string,mixed> */
    private function query(array $options): array
    {
        return [
            'config_id' => $options['config_id'] ?? null, 'status' => $options['status'] ?? null,
            'event_type' => $options['event_type'] ?? null, 'from_date' => $options['from_date'] ?? null,
            'to_date' => $options['to_date'] ?? null, 'page' => $options['page'] ?? null,
            'limit' => $options['limit'] ?? null,
        ];
    }
}
