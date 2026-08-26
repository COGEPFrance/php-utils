<?php

namespace Cogep\PhpUtils\Health;

use Cogep\PhpUtils\Classes\Responses\StandardResponseDto;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class HealthQueryHandler
{
    public function __invoke(HealthQuery $query): StandardResponseDto
    {
        return StandardResponseDto::success([
            'status' => 'ok',
        ]);
    }
}
