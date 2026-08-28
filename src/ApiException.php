<?php

declare(strict_types=1);

namespace MonaPay;

use RuntimeException;

final class ApiException extends RuntimeException
{
    /** @var int|null */
    public $status;

    /** @var mixed */
    public $body;

    /** @param mixed $body */
    public function __construct(string $message, ?int $status = null, $body = null)
    {
        parent::__construct($message);
        $this->status = $status;
        $this->body = $body;
    }
}
