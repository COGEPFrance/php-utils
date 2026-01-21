<?php

namespace Unit\Logs;

use Cogep\PhpUtils\Logs\LoggerFormator;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class LoggerFormatterTest extends TestCase
{
    private LoggerFormator $formatter;

    protected function setUp(): void
    {
        $this->formatter = new LoggerFormator();
    }

    /**
     * Teste le formatage d'un enregistrement unique avec couleur.
     */
    public function testFormatLevelInfoHasGreenColor(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2026-01-20 10:00:00'),
            channel: 'app',
            level: Level::Info,
            message: 'Message de test'
        );

        $output = $this->formatter->format($record);

        $this->assertStringContainsString("\033[32m[INFO]\033[0m", $output);
        $this->assertStringContainsString('2026-01-20 10:00:00', $output);
        $this->assertStringContainsString('Message de test', $output);
        $this->assertStringEndsWith("\n", $output);
    }

    /**
     * Teste le formatage pour un niveau avec une couleur différente.
     */
    public function testFormatLevelErrorHasRedColor(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable('2026-01-20 10:00:00'),
            channel: 'app',
            level: Level::Error,
            message: 'Alerte rouge'
        );

        $output = $this->formatter->format($record);

        $this->assertStringContainsString("\033[31m[ERROR]\033[0m", $output);
    }

    public function testFormatBatch(): void
    {
        $records = [
            new LogRecord(new \DateTimeImmutable(), 'app', Level::Debug, 'Msg 1'),
            new LogRecord(new \DateTimeImmutable(), 'app', Level::Warning, 'Msg 2'),
        ];

        $batchOutput = $this->formatter->formatBatch($records);

        $this->assertIsArray($batchOutput);
        $this->assertCount(2, $batchOutput);
        $this->assertStringContainsString('[DEBUG]', $batchOutput[0]);
        $this->assertStringContainsString('[WARNING]', $batchOutput[1]);
    }
}
