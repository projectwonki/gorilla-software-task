<?php

declare(strict_types=1);

namespace App\Model;

use JsonSerializable;

class Inspection implements JsonSerializable
{
    public function __construct(
        public string $description,
        public ?string $inspectionDate,
        public ?int $weekOfYear,
        public string $status,
        public ?string $clientPhone,
        public string $createdAt,
        public ?string $recommendations = null,
        public string $type = 'inspection'
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'description' => $this->description,
            'type' => $this->type,
            'inspectionDate' => $this->inspectionDate,
            'weekOfYear' => $this->weekOfYear,
            'status' => $this->status,
            'recommendations' => $this->recommendations,
            'clientPhone' => $this->clientPhone,
            'createdAt' => $this->createdAt,
        ];
    }
}
