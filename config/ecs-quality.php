<?php

declare(strict_types=1);


use PHP_CodeSniffer\Standards\Generic\Sniffs\Metrics\CyclomaticComplexitySniff;
use SlevomatCodingStandard\Sniffs\Complexity\CognitiveSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return static function (ECSConfig $ecsConfig): void {
    $projectRoot = dirname(__DIR__);

    $ecsConfig->paths([
        $projectRoot . '/src',
    ]);

    $ecsConfig->ruleWithConfiguration(CognitiveSniff::class, [
        'maxComplexity' => 10
    ]);

    $ecsConfig->ruleWithConfiguration(CyclomaticComplexitySniff::class, [
        'absoluteComplexity' => 10,
    ]);
};
