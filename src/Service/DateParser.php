<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use Throwable;

class DateParser
{
    /**
     * Parses a date value into a DateTimeImmutable instance.
     * Returns null if the value is empty, null, or cannot be parsed.
     */
    public function parse(?string $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($trimmed);
        } catch (Throwable) {
            return null;
        }
    }
}
