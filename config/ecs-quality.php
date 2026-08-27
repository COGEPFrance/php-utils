<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\Metrics\CyclomaticComplexitySniff;
use SlevomatCodingStandard\Sniffs\Complexity\CognitiveSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        dirname(__DIR__) . '/src',
    ])
    ->withConfiguredRule(CognitiveSniff::class, [
        'maxComplexity' => 10,
    ])
    ->withConfiguredRule(CyclomaticComplexitySniff::class, [
        'absoluteComplexity' => 10,
    ]);
