<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class FailureReport implements JsonSerializable
{
    public function __construct(
        public string $description,
        public string $priority,
        public ?string $serviceVisitDate,
        public string $status,
        public ?string $clientPhone,
        public string $createdAt,
        public ?string $serviceNotes = null,
        public string $type = 'failure_report'
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'serviceVisitDate' => $this->serviceVisitDate,
            'status' => $this->status,
            'serviceNotes' => $this->serviceNotes,
            'clientPhone' => $this->clientPhone,
            'createdAt' => $this->createdAt,
        ];
    }
}
