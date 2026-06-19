<?php

declare(strict_types=1);

namespace App\Service;

class MessageClassifier
{
    public function classify(string $description): string
    {
        $lowerDescription = mb_strtolower($description);

        if (str_contains($lowerDescription, 'przegląd') || str_contains($lowerDescription, 'inspection')) {
            return 'inspection';
        }

        return 'failure_report';
    }
}
