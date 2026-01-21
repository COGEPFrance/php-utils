<?php

namespace Cogep\PhpUtils\Tests\Dev;

use Composer\Script\Event;
use Exception;
use RuntimeException;
use SimpleXMLElement;

class CoverageChecker
{
    public static function run(Event $event): void
    {
        $args = $event->getArguments();
        $threshold = isset($args[0]) ? (int) $args[0] : 80;

        self::check($threshold);
    }

    public static function calculate(string $cloverFile = 'clover.xml'): float
    {
        if (! file_exists($cloverFile)) {
            throw new RuntimeException("Fichier {$cloverFile} non trouvé.");
        }

        $xml = simplexml_load_file($cloverFile);

        if (! $xml instanceof SimpleXMLElement || ! isset($xml->project) || ! isset($xml->project->metrics)) {
            throw new RuntimeException('Format du fichier de coverage invalide.');
        }

        $metrics = $xml->project->metrics;
        $total = (float) $metrics['elements'];
        $covered = (float) $metrics['coveredelements'];

        return ($total > 0) ? round(($covered / $total) * 100, 2) : 0;
    }

    public static function check(int $threshold = 80, bool $shouldExit = true): void
    {
        try {
            $percent = self::calculate();
            file_put_contents('coverage_score.txt', (string) $percent);

            if ($percent < $threshold) {
                echo "\033[31mFAIL: Couverture de {$percent}% inférieure au seuil de 80%\033[0m\n";
                if ($shouldExit) {
                    exit(1);
                }
            }

            echo "\033[32mSUCCESS: Couverture de {$percent}% validée !\033[0m\n";
        } catch (Exception $e) {
            echo "\033[31mERROR: " . $e->getMessage() . "\033[0m\n";
            if ($shouldExit) {
                exit(1);
            }
        }
    }
}
