<?php


namespace Cogep\PhpUtils\Logs;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class UrlTruncatorProcessor implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $message = $record->message;
        $context = $record->context;

        if (strlen($message) > 500) {
            $newMessage = substr($message, 0, 500) . '...';

            return $record->with(message: $newMessage);
        }

        array_walk_recursive($context, function (&$value) {
            if (is_string($value) && strlen($value) > 1000) {
                $value = substr($value, 0, 1000) . '...';
            }
        });

        return $record->with(message: $message, context: $context);
    }
}
