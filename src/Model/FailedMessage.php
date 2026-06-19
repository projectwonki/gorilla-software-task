<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class FailedMessage implements JsonSerializable
{
    public function __construct(
        public array $originalMessage,
        public string $reason
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'originalMessage' => $this->originalMessage,
            'reason' => $this->reason,
        ];
    }
}
