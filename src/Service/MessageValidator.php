<?php

declare(strict_types=1);

namespace App\Service;

class MessageValidator
{
    /**
     * Validates that the raw message has the required fields.
     * Returns true if valid, or a string reason if invalid.
     *
     * @param mixed $message
     * @return true|string
     */
    public function validate(mixed $message): true|string
    {
        if (!is_array($message)) {
            return 'Message is not a valid JSON object';
        }

        if (!isset($message['number']) || (!is_int($message['number']) && !is_numeric($message['number']))) {
            return 'Missing or invalid "number" field';
        }

        if (!isset($message['description'])) {
            return 'Missing "description" field';
        }

        if ($message['description'] === null) {
            return 'Description field cannot be null';
        }

        if (!is_string($message['description'])) {
            return 'Description field must be a string';
        }

        if (trim($message['description']) === '') {
            return 'Description field cannot be empty';
        }

        return true;
    }
}
