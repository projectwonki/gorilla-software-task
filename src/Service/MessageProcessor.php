<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\FailedMessage;
use App\Model\FailureReport;
use App\Model\Inspection;
use App\Service\Classifier\ClassifierInterface;
use DateTimeImmutable;

class MessageProcessor
{
    private array $processedDescriptions = [];

    public function __construct(
        private MessageValidator $validator,
        private ClassifierInterface $classifier,
        private DateParser $dateParser
    ) {}

    /**
     * Resets the deduplication state.
     */
    public function reset(): void
    {
        $this->processedDescriptions = [];
    }

    /**
     * Process a list of raw messages.
     * Returns an array with inspections, failure reports, and failed messages.
     */
    public function process(array $messages, ?string $currentTime = null): array
    {
        $this->reset();

        $inspections = [];
        $failureReports = [];
        $failedMessages = [];

        $createdAt = $currentTime ?? (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);

        foreach ($messages as $msg) {
            // 1. Validation
            $validationResult = $this->validator->validate($msg);
            if ($validationResult !== true) {
                $failedMessages[] = new FailedMessage(
                    is_array($msg) ? $msg : ['raw' => $msg],
                    $validationResult
                );
                continue;
            }

            $description = $msg['description'];

            // 2. Deduplication check
            $normalizedDesc = trim(mb_strtolower($description));
            if (isset($this->processedDescriptions[$normalizedDesc])) {
                $failedMessages[] = new FailedMessage(
                    $msg,
                    'Duplicate description (already produced an entity)'
                );
                continue;
            }

            // 3. Classification
            $type = $this->classifier->classify($description);

            // 4. Phone number parsing/mapping
            $clientPhone = $this->mapPhone($msg['phone'] ?? null);

            // 5. Build entities based on classification
            if ($type === 'inspection') {
                $dueDateStr = isset($msg['dueDate']) ? (string)$msg['dueDate'] : null;
                $dueDate = $this->dateParser->parse($dueDateStr);

                if ($dueDate !== null) {
                    $inspectionDate = $dueDate->format('Y-m-d');
                    $weekOfYear = (int) $dueDate->format('W');
                    $status = 'scheduled';
                } else {
                    $inspectionDate = null;
                    $weekOfYear = null;
                    $status = 'new';
                }

                $inspections[] = new Inspection(
                    description: $description,
                    inspectionDate: $inspectionDate,
                    weekOfYear: $weekOfYear,
                    status: $status,
                    clientPhone: $clientPhone,
                    createdAt: $createdAt
                );
            } else {
                $dueDateStr = isset($msg['dueDate']) ? (string)$msg['dueDate'] : null;
                $dueDate = $this->dateParser->parse($dueDateStr);

                if ($dueDate !== null) {
                    $serviceVisitDate = $dueDate->format('Y-m-d');
                    $status = 'appointment';
                } else {
                    $serviceVisitDate = null;
                    $status = 'new';
                }

                $priority = $this->determinePriority($description);

                $failureReports[] = new FailureReport(
                    description: $description,
                    priority: $priority,
                    serviceVisitDate: $serviceVisitDate,
                    status: $status,
                    clientPhone: $clientPhone,
                    createdAt: $createdAt
                );
            }

            // Mark this description as processed
            $this->processedDescriptions[$normalizedDesc] = true;
        }

        return [
            'inspections' => $inspections,
            'failure_reports' => $failureReports,
            'failed_messages' => $failedMessages,
        ];
    }

    /**
     * Map phone number according to rules.
     * Returns null if missing/empty/invalid.
     */
    private function mapPhone(mixed $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phoneStr = trim((string)$phone);
        if ($phoneStr === '') {
            return null;
        }

        // Rule: "If duplicate/missing/empty -> leave empty. Any fields you cannot determine — leave empty."
        // Let's filter out stray characters or strings with no digits (like '"')
        if (preg_match('/[0-9]/', $phoneStr) === 0) {
            return null;
        }

        return $phoneStr;
    }

    /**
     * Determine priority based on description content.
     */
    private function determinePriority(string $description): string
    {
        $lowerDesc = mb_strtolower($description);

        // Check for negations to treat as normal priority
        if (str_contains($lowerDesc, 'not urgent') || str_contains($lowerDesc, 'nie pilne') || str_contains($lowerDesc, 'not very urgent')) {
            return 'normal';
        }

        // Check for critical priority first (case-insensitive)
        if (str_contains($lowerDesc, 'bardzo pilne') || str_contains($lowerDesc, 'very urgent')) {
            return 'critical';
        }

        // Check for high priority (case-insensitive)
        if (str_contains($lowerDesc, 'pilne') || str_contains($lowerDesc, 'urgent')) {
            return 'high';
        }

        return 'normal';
    }
}
