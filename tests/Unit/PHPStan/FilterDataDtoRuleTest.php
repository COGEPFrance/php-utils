<?php

namespace Cogep\PhpUtils\Tests\Unit\PHPStan;

use PHPUnit\Framework\TestCase;

class FilterDataDtoRuleTest extends TestCase
{
    private string $phpstanBin;

    private string $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 3);
        $this->phpstanBin = $this->projectRoot . '/vendor/bin/phpstan';
    }

    public function testBadFilterDataDtoTriggersAllErrors(): void
    {
        $fixture = $this->projectRoot . '/tests/Fixtures/PHPStan/BadFilterDataDto.php';

        $output = $this->runPhpStan($fixture);

        $this->assertStringContainsString('Property $filter', $output);
        $this->assertStringContainsString('Property $data', $output);
        $this->assertStringContainsString('"array" is not allowed', $output);
        $this->assertStringContainsString('"object" is not allowed', $output);
        $this->assertStringContainsString('#[Valid]', $output);
        $this->assertStringContainsString('#[NotNull]', $output);
    }

    public function testGoodFilterDataDtoTriggersNoRuleErrors(): void
    {
        $fixture = $this->projectRoot . '/tests/Fixtures/FilterData/FilterDataCommandFixture.php';

        $output = $this->runPhpStan($fixture);

        $this->assertStringNotContainsString('filterDataDto.', $output);
    }

    private function runPhpStan(string $file): string
    {
        $configFile = $this->projectRoot . '/phpstan.neon';

        $cmd = sprintf(
            'php %s analyse %s -c %s --no-progress --error-format=raw 2>&1',
            escapeshellarg($this->phpstanBin),
            escapeshellarg($file),
            escapeshellarg($configFile)
        );

        exec($cmd, $outputLines);

        return implode("\n", $outputLines);
    }
}
