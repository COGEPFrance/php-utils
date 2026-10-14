<?php

namespace Cogep\PhpUtils\Tests\Unit\Logs;

use Cogep\PhpUtils\Logs\SensitiveDataRedactorProcessor;
use Cogep\PhpUtils\Tests\BaseMockeryTestCase;
use Monolog\Level;
use Monolog\LogRecord;

class SensitiveDataRedactorProcessorTest extends BaseMockeryTestCase
{
    private SensitiveDataRedactorProcessor $processor;

    protected function setUp(): void
    {
        $this->processor = new SensitiveDataRedactorProcessor();
    }

    public function testAzureSasTokenStartingWithSvIsRedacted(): void
    {
        $message = 'Response: "201 https://account.blob.core.windows.net/container/'
            . 'dossier.csv?sv=2024-11-04&se=2026-01-01&sig=AbCdEf123%2B%2F%3D" 0.963353 seconds';

        $record = $this->makeRecord($message);
        $newRecord = ($this->processor)($record);

        $this->assertStringNotContainsString('sv=2024-11-04', $newRecord->message);
        $this->assertStringNotContainsString('sig=AbCdEf123', $newRecord->message);
        $this->assertStringContainsString('?[redacted]', $newRecord->message);
    }

    public function testHostIsRedacted(): void
    {
        $message = 'Response: "201 https://mystorageaccount.blob.core.windows.net/'
            . 'container/dossier.csv?sv=2024-11-04" 0.5 seconds';

        $record = $this->makeRecord($message);
        $newRecord = ($this->processor)($record);

        $this->assertStringNotContainsString('mystorageaccount.blob.core.windows.net', $newRecord->message);
        $this->assertStringContainsString('[redacted-host]', $newRecord->message);
        $this->assertStringContainsString('/container/dossier.csv', $newRecord->message);
    }

    public function testUrlWithoutQueryStringKeepsPathButRedactsHost(): void
    {
        $message = 'Calling https://api.example.com/v1/resource';

        $record = $this->makeRecord($message);
        $newRecord = ($this->processor)($record);

        $this->assertSame('Calling https://[redacted-host]/v1/resource', $newRecord->message);
    }

    public function testContextUrlsAreRedactedRecursively(): void
    {
        $record = $this->makeRecord('Short message', [
            'http' => [
                'url' => 'https://account.blob.core.windows.net/container/file.txt?sv=2024&sig=secret',
            ],
        ]);

        $newRecord = ($this->processor)($record);

        $redactedUrl = $newRecord->context['http']['url'];
        $this->assertStringNotContainsString('account.blob.core.windows.net', $redactedUrl);
        $this->assertStringNotContainsString('sig=secret', $redactedUrl);
    }

    public function testBearerTokenIsRedacted(): void
    {
        $record = $this->makeRecord('Authorization header: Bearer abcDEF123.456-789_xyz');

        $newRecord = ($this->processor)($record);

        $this->assertStringNotContainsString('abcDEF123.456-789_xyz', $newRecord->message);
        $this->assertStringContainsString('Bearer [redacted]', $newRecord->message);
    }

    public function testMessageWithoutUrlIsUnchanged(): void
    {
        $record = $this->makeRecord('Simple message without secrets');

        $newRecord = ($this->processor)($record);

        $this->assertSame($record->message, $newRecord->message);
    }

    /**
     * @param array<mixed,mixed> $context
     */
    private function makeRecord(string $message, array $context = []): LogRecord
    {
        return new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: Level::Info,
            message: $message,
            context: $context
        );
    }
}
