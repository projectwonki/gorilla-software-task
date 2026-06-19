<?php

declare(strict_types=1);

namespace App\Service\Classifier;

interface ClassifierInterface
{
    /**
     * Classifies a message description.
     * Returns either 'inspection' or 'failure_report'.
     */
    public function classify(string $description): string;
}
