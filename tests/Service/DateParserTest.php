<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DateParser;
use PHPUnit\Framework\TestCase;

class DateParserTest extends TestCase
{
    private DateParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DateParser();
    }

    /**
     * @dataProvider dateDataProvider
     */
    public function testParse(?string $input, ?string $expectedFormat, ?string $expectedValue): void
    {
        $parsed = $this->parser->parse($input);

        if ($expectedValue === null) {
            $this->assertNull($parsed);
        } else {
            $this->assertNotNull($parsed);
            $this->assertSame($expectedValue, $parsed->format($expectedFormat));
        }
    }

    public static function dateDataProvider(): array
    {
        return [
            // Valid inputs
            ['2020-01-04 13:30:00', 'Y-m-d H:i:s', '2020-01-04 13:30:00'],
            ['2020-02-03 15:15', 'Y-m-d H:i', '2020-02-03 15:15'],
            ['2020-02-15', 'Y-m-d', '2020-02-15'],
            [' 2020-02-15 ', 'Y-m-d', '2020-02-15'], // whitespace trimmed

            // Empty/null inputs
            ['', 'Y-m-d', null],
            ['   ', 'Y-m-d', null],
            [null, 'Y-m-d', null],

            // Invalid inputs
            ['not-a-date', 'Y-m-d', null],
            ['2020-99-99', 'Y-m-d', null],
        ];
    }
}
