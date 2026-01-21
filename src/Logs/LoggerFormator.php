<?php


namespace Cogep\PhpUtils\Logs;


use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

class LoggerFormator implements FormatterInterface
{
    private const COLORS = [
        'DEBUG' => "\033[36m", // Cyan
        'INFO' => "\033[32m", // Vert
        'WARNING' => "\033[33m", // Jaune
        'ERROR' => "\033[31m", // Rouge
        'CRITICAL' => "\033[35m", // Magenta
    ];

    public function format(LogRecord $record): string
    {
        $levelName = strtoupper($record->level->name);
        $color = self::COLORS[$levelName] ?? '';
        $reset = "\033[0m";

        return sprintf(
            "[%s] %s[%s]%s %s\n",
            $record->datetime->format('Y-m-d H:i:s'),
            $color,
            $levelName,
            $reset,
            $record->message
        );
    }

    public function formatBatch(array $records)
    {
        return array_map(fn($record) => $this->format($record), $records);
    }
}
