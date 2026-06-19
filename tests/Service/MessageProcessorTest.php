<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Classifier\KeywordClassifier;
use App\Service\DateParser;
use App\Service\MessageProcessor;
use App\Service\MessageValidator;
use PHPUnit\Framework\TestCase;

class MessageProcessorTest extends TestCase
{
    private MessageProcessor $processor;

    protected function setUp(): void
    {
        $validator = new MessageValidator();
        $classifier = new KeywordClassifier();
        $dateParser = new DateParser();
        $this->processor = new MessageProcessor($validator, $classifier, $dateParser);
    }

    public function testProcessSuccessful(): void
    {
        $messages = [
            // 1. Failure Report with appointment (valid date)
            [
                'number' => 1,
                'description' => 'Air conditioning failure. Urgent repair needed.',
                'dueDate' => '2020-01-04 13:30:00',
                'phone' => '666-445-127'
            ],
            // 2. Inspection with scheduled (valid date)
            [
                'number' => 2,
                'description' => 'Inspection of the electrical installation.',
                'dueDate' => '2020-02-15',
                'phone' => '+48606303404'
            ],
            // 3. Failure Report with new status (no date)
            [
                'number' => 3,
                'description' => 'Plaster falling off. Not urgent.',
                'dueDate' => '',
                'phone' => '888000128'
            ],
            // 4. Inspection with new status (no date)
            [
                'number' => 4,
                'description' => 'We need an inspection of sewage pipes.',
                'dueDate' => null,
                'phone' => null
            ],
            // 5. Critical Priority Failure Report
            [
                'number' => 5,
                'description' => 'Very urgent. Boiler failure in the storage room.',
                'dueDate' => '2020-03-01',
                'phone' => '876123567'
            ]
        ];

        $currentTime = '2026-06-19T06:00:00Z';
        $result = $this->processor->process($messages, $currentTime);

        $this->assertCount(2, $result['inspections']);
        $this->assertCount(3, $result['failure_reports']);
        $this->assertCount(0, $result['failed_messages']);

        // Inspect first failure report (Appointment status, High priority)
        $fr1 = $result['failure_reports'][0];
        $this->assertSame('Air conditioning failure. Urgent repair needed.', $fr1->description);
        $this->assertSame('failure_report', $fr1->type);
        $this->assertSame('high', $fr1->priority);
        $this->assertSame('2020-01-04', $fr1->serviceVisitDate);
        $this->assertSame('appointment', $fr1->status);
        $this->assertSame('666-445-127', $fr1->clientPhone);
        $this->assertSame($currentTime, $fr1->createdAt);

        // Inspect second failure report (New status, Normal priority)
        $fr2 = $result['failure_reports'][1];
        $this->assertSame('Plaster falling off. Not urgent.', $fr2->description);
        $this->assertSame('normal', $fr2->priority);
        $this->assertNull($fr2->serviceVisitDate);
        $this->assertSame('new', $fr2->status);

        // Inspect critical failure report
        $fr3 = $result['failure_reports'][2];
        $this->assertSame('critical', $fr3->priority);

        // Inspect first inspection (Scheduled status)
        $ins1 = $result['inspections'][0];
        $this->assertSame('Inspection of the electrical installation.', $ins1->description);
        $this->assertSame('inspection', $ins1->type);
        $this->assertSame('2020-02-15', $ins1->inspectionDate);
        $this->assertSame(7, $ins1->weekOfYear); // Feb 15, 2020 is in week 7
        $this->assertSame('scheduled', $ins1->status);
        $this->assertSame('+48606303404', $ins1->clientPhone);
        $this->assertSame($currentTime, $ins1->createdAt);

        // Inspect second inspection (New status)
        $ins2 = $result['inspections'][1];
        $this->assertNull($ins2->inspectionDate);
        $this->assertNull($ins2->weekOfYear);
        $this->assertSame('new', $ins2->status);
        $this->assertNull($ins2->clientPhone);
    }

    public function testDuplicateDetection(): void
    {
        $messages = [
            [
                'number' => 1,
                'description' => 'Boiler failure.',
                'dueDate' => '',
                'phone' => ''
            ],
            [
                'number' => 2,
                'description' => 'boiler failure.', // case insensitive duplicate
                'dueDate' => '',
                'phone' => ''
            ]
        ];

        $result = $this->processor->process($messages);
        $this->assertCount(1, $result['failure_reports']);
        $this->assertCount(1, $result['failed_messages']);
        $this->assertSame('Duplicate description (already produced an entity)', $result['failed_messages'][0]->reason);
    }

    public function testValidationFailures(): void
    {
        $messages = [
            [
                'number' => 1,
                // missing description
                'dueDate' => '',
                'phone' => ''
            ],
            [
                // missing number
                'description' => 'Valid description',
                'dueDate' => '',
                'phone' => ''
            ],
            [
                'number' => 3,
                'description' => '', // empty description
                'dueDate' => '',
                'phone' => ''
            ],
            'not-an-array'
        ];

        $result = $this->processor->process($messages);
        $this->assertCount(0, $result['inspections']);
        $this->assertCount(0, $result['failure_reports']);
        $this->assertCount(4, $result['failed_messages']);
    }

    public function testStrayPhoneQuote(): void
    {
        $messages = [
            [
                'number' => 1,
                'description' => 'Leaking tap',
                'dueDate' => '',
                'phone' => '"' // Stray quote phone number
            ]
        ];

        $result = $this->processor->process($messages);
        $fr = $result['failure_reports'][0];
        $this->assertNull($fr->clientPhone);
    }
}
