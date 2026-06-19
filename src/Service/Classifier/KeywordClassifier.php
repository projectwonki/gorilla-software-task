<?php

declare(strict_types=1);

namespace App\Service\Classifier;

class KeywordClassifier implements ClassifierInterface
{
    /**
     * Classifies a message description based on description keywords.
     * Checks for the Polish phrase "przegląd" and the English equivalent "inspection" (case-insensitive).
     */
    public function classify(string $description): string
    {
        $lowerDescription = mb_strtolower($description);

        if (str_contains($lowerDescription, 'przegląd') || str_contains($lowerDescription, 'inspection')) {
            return 'inspection';
        }

        return 'failure_report';
    }
}
