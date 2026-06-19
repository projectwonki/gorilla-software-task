<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\FailedMessage;
use App\Model\FailureReport;
use App\Model\Inspection;
use DateTimeImmutable;

class MessageProcessor
{
    private array $processedDescriptions = [];

    public function __construct(
        private MessageValidator $validator,
        private MessageClassifier $classifier,
        private DateParser $dateParser
    ) {}

    public function reset(): void
    {
        $this->processedDescriptions = [];
    }

    public function process(array $messages, ?string $currentTime = null): array
    {
        $this->reset();

        $inspections = [];
        $failureReports = [];
        $failedMessages = [];

        $createdAt = $currentTime ?? (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);

        foreach ($messages as $msg) {
            // Validate incoming message structure
            $validationResult = $this->validator->validate($msg);
            if ($validationResult !== true) {
                $failedMessages[] = new FailedMessage(
                    is_array($msg) ? $msg : ['raw' => $msg],
                    $validationResult
                );
                continue;
            }

            $description = $msg['description'];

            // Prevent duplicate entities from the same description
            $normalizedDesc = trim(mb_strtolower($description));
            if (isset($this->processedDescriptions[$normalizedDesc])) {
                $failedMessages[] = new FailedMessage(
                    $msg,
                    'Duplicate description (already produced an entity)'
                );
                continue;
            }

            $type = $this->classifier->classify($description);
            $clientPhone = $this->mapPhone($msg['phone'] ?? null);

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

            $this->processedDescriptions[$normalizedDesc] = true;
        }

        return [
            'inspections' => $inspections,
            'failure_reports' => $failureReports,
            'failed_messages' => $failedMessages,
        ];
    }

    private function mapPhone(mixed $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phoneStr = trim((string)$phone);
        if ($phoneStr === '') {
            return null;
        }

        // Clean up stray quotes/characters and only accept valid digits
        if (preg_match('/[0-9]/', $phoneStr) === 0) {
            return null;
        }

        return $phoneStr;
    }

    private function determinePriority(string $description): string
    {
        $lowerDesc = mb_strtolower($description);

        if (str_contains($lowerDesc, 'not urgent') || str_contains($lowerDesc, 'nie pilne') || str_contains($lowerDesc, 'not very urgent')) {
            return 'normal';
        }

        if (str_contains($lowerDesc, 'bardzo pilne') || str_contains($lowerDesc, 'very urgent')) {
            return 'critical';
        }

        if (str_contains($lowerDesc, 'pilne') || str_contains($lowerDesc, 'urgent')) {
            return 'high';
        }

        return 'normal';
    }
}
