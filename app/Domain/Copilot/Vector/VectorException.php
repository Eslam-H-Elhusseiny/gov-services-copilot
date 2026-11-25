<?php

namespace App\Domain\Copilot\Vector;

use RuntimeException;

class VectorException extends RuntimeException
{
    public function __construct(
        public string $provider,
        string $message,
        public ?int $statusCode = null,
        public bool $retryable = false,
        public ?string $raw = null,
    ) {
        parent::__construct($message);
    }
}
