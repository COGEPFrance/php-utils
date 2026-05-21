<?php

namespace Cogep\PhpUtils\Tests\Unit\Logs;

use Cogep\PhpUtils\Logs\UrlTruncatorProcessor;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Monolog\Level;
use Monolog\LogRecord;

class UrlTruncatorProcessorTest extends BaseMockeryTestCase
{
    private UrlTruncatorProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new UrlTruncatorProcessor();
    }

    public function testMessageIsTruncatedWhenTooLong(): void
    {
        $longMessage = str_repeat('a', 600);
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: $longMessage
        );

        $newRecord = ($this->processor)($record);

        $this->assertNotSame($record, $newRecord);
        $this->assertLessThan(strlen($longMessage), strlen($newRecord->message));
        $this->assertStringEndsWith('...', $newRecord->message);
        $this->assertEquals(503, strlen($newRecord->message)); // 500 + '...'
    }

    public function testContextStringsAreTruncatedRecursively(): void
    {
        $longUrl = 'https://api.test.com/' . str_repeat('x', 1100);
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Short message',
            context: [
                'api' => [
                    'url' => $longUrl,
                ],
            ]
        );

        $newRecord = ($this->processor)($record);

        $truncatedUrl = $newRecord->context['api']['url'];
        $this->assertLessThan(strlen($longUrl), strlen($truncatedUrl));
        $this->assertStringEndsWith('...', $truncatedUrl);
        $this->assertEquals(1003, strlen($truncatedUrl));
    }

    public function testRecordUnchangedWhenBelowLimits(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: 'Short message',
            context: [
                'key' => 'short value',
            ]
        );

        $newRecord = ($this->processor)($record);

        $this->assertSame($record->message, $newRecord->message);
        $this->assertSame($record->context, $newRecord->context);
    }
}
