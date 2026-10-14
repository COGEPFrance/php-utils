<?php

namespace Cogep\PhpUtils\Logs;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class SensitiveDataRedactorProcessor implements ProcessorInterface
{
    private const REDACTED = '[redacted]';

    private const REDACTED_HOST = '[redacted-host]';

    public function __invoke(LogRecord $record): LogRecord
    {
        $message = $this->redact($record->message);
        $context = $this->redactRecursive($record->context);

        return $record->with(message: $message, context: $context);
    }

    /**
     * @param array<mixed,mixed> $context
     *
     * @return array<mixed,mixed>
     */
    private function redactRecursive(array $context): array
    {
        array_walk_recursive($context, function (&$value): void {
            if (is_string($value)) {
                $value = $this->redact($value);
            }
        });

        return $context;
    }

    private function redact(string $value): string
    {
        $redacted = $this->redactUrls($value);

        return $this->redactBearerTokens($redacted);
    }

    /**
     * Masque le host (y compris d'éventuels credentials "user:pass@") et
     * toute la query string de chaque URL http(s) trouvée dans la chaîne.
     */
    private function redactUrls(string $value): string
    {
        return preg_replace_callback(
            '#(https?://)([^/\s"\']+)([^\s"\']*)#i',
            function (array $matches): string {
                [, $scheme, , $remainder] = $matches;
                $queryPosition = strpos($remainder, '?');
                $path = $queryPosition === false ? $remainder : substr($remainder, 0, $queryPosition);

                return $scheme . self::REDACTED_HOST . $path . ($queryPosition === false ? '' : '?' . self::REDACTED);
            },
            $value
        ) ?? $value;
    }

    private function redactBearerTokens(string $value): string
    {
        return preg_replace(
            '/(Bearer\s+)[A-Za-z0-9\-_.]+/i',
            '$1' . self::REDACTED,
            $value
        ) ?? $value;
    }
}
