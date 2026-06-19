<?php

declare(strict_types=1);

namespace App\Service;

use DateTimeImmutable;
use Throwable;

class DateParser
{
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
