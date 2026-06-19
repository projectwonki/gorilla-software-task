<?php

declare(strict_types=1);

namespace App\Service;

class MessageValidator
{
    public function validate(mixed $message): true|string
    {
        if (!is_array($message)) {
            return 'Message is not a valid JSON object';
        }

        if (!isset($message['number']) || !is_numeric($message['number'])) {
            return 'Missing or invalid "number" field';
        }

        $desc = $message['description'] ?? null;
        if ($desc === null) {
            return 'Missing "description" field';
        }

        if (!is_string($desc) || trim($desc) === '') {
            return 'Description field must be a non-empty string';
        }

        return true;
    }
}
