<?php

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $ecsConfig): void {
    $projectRoot = dirname(__DIR__);

    $ecsConfig->paths([
        $projectRoot . '/src',
        $projectRoot . '/tests',
    ]);

    $ecsConfig->sets([
        SetList::PSR_12,
        SetList::CLEAN_CODE,
        SetList::COMMON,
        SetList::SYMPLIFY,
    ]);
};
