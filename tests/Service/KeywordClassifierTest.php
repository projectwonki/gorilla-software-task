<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\Classifier\KeywordClassifier;
use PHPUnit\Framework\TestCase;

class KeywordClassifierTest extends TestCase
{
    private KeywordClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new KeywordClassifier();
    }

    /**
     * @dataProvider classificationDataProvider
     */
    public function testClassify(string $description, string $expectedType): void
    {
        $this->assertSame($expectedType, $this->classifier->classify($description));
    }

    public static function classificationDataProvider(): array
    {
        return [
            // Polish cases
            ['Zgłoszenie na przegląd klimatyzacji', 'inspection'],
            ['PRZEGLĄD okresowy windy', 'inspection'],
            ['przegląd', 'inspection'],

            // English cases
            ['Requesting an inspection of the sewage pipes', 'inspection'],
            ['INSPECTION of electrical installation', 'inspection'],
            ['inspection', 'inspection'],

            // Failure reports
            ['Air conditioning failure. The temperature in the showroom has risen.', 'failure_report'],
            ['Leaking tap in the back room. Wet shoes!', 'failure_report'],
            ['Request for a visit', 'failure_report'],
            ['No internet in the staff room', 'failure_report'],
        ];
    }
}
