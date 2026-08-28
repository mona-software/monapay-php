<?php

declare(strict_types=1);

namespace MonaPay;

use InvalidArgumentException;

final class Webhook
{
    /**
     * @param array<string,mixed> $headers
     * @return array{ok:bool,reason?:string,payload?:mixed}
     */
    public static function verify(string $rawBody, array $headers, string $secret, int $tolerance = 300): array
    {
        if ($tolerance < 0) {
            throw new InvalidArgumentException('tolerance phải là số không âm');
        }

        $timestampText = self::header($headers, 'x-mona-timestamp');
        $signature = self::header($headers, 'x-mona-signature');
        if ($timestampText === null || $timestampText === '') {
            return ['ok' => false, 'reason' => 'missing_timestamp'];
        }
        if (!preg_match('/^\d+$/', $timestampText)) {
            return ['ok' => false, 'reason' => 'invalid_timestamp'];
        }
        $timestamp = (int) $timestampText;
        if (abs(time() - $timestamp) > $tolerance) {
            return ['ok' => false, 'reason' => 'timestamp_out_of_tolerance'];
        }
        if ($signature === null || $signature === '') {
            return ['ok' => false, 'reason' => 'missing_signature'];
        }

        $expected = hash_hmac('sha256', $timestampText . '.' . $rawBody, $secret);
        $matchesFormat = preg_match('/^sha256=([0-9a-fA-F]{64})$/', $signature, $matches) === 1;
        $supplied = $matchesFormat ? strtolower($matches[1]) : str_repeat('0', 64);
        $signatureOk = hash_equals($expected, $supplied) && $matchesFormat;
        if (!$signatureOk) {
            return ['ok' => false, 'reason' => 'invalid_signature'];
        }

        $payload = json_decode($rawBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['ok' => false, 'reason' => 'invalid_json'];
        }
        return ['ok' => true, 'payload' => $payload];
    }

    /** @param array<string,mixed> $headers */
    private static function header(array $headers, string $wanted): ?string
    {
        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) !== $wanted) {
                continue;
            }
            if (is_array($value)) {
                $value = count($value) > 0 ? reset($value) : null;
            }
            return $value === null ? null : (string) $value;
        }
        return null;
    }
}
