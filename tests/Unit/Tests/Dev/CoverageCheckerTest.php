<?php

namespace Unit\Tests\Dev;

use Cogep\PhpUtils\Tests\Dev\CoverageChecker;
use PHPUnit\Framework\TestCase;

class CoverageCheckerTest extends TestCase
{
    private string $cloverFile = 'clover.xml';
    private string $scoreFile = 'coverage_score.txt';

    const string XML_COVER_90 =
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <coverage>
                <project>
                    <metrics elements=\"100\" coveredelements=\"90\"/>
                </project>
            </coverage>";

    protected function tearDown(): void
    {
        if (file_exists($this->cloverFile)) unlink($this->cloverFile);
        if (file_exists($this->scoreFile)) unlink($this->scoreFile);
    }

    public function testCalculateSuccess(): void
    {
        file_put_contents($this->cloverFile, self::XML_COVER_90);
        $score = CoverageChecker::calculate($this->cloverFile);
        $this->assertEquals(90, $score);
    }

    public function testCalculateThrowsExceptionIfFileMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('non trouvé');
        CoverageChecker::calculate('inexistant.xml');
    }

    public function testCalculateThrowsExceptionOnInvalidFormat(): void
    {
        file_put_contents($this->cloverFile, "<invalid></invalid>");
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Format du fichier de coverage invalide");
        CoverageChecker::calculate($this->cloverFile);
    }

    public function testCheckOutputsSuccess(): void
    {
        file_put_contents($this->cloverFile, self::XML_COVER_90);

        ob_start();
        CoverageChecker::check(80, false);
        $output = ob_get_clean();

        $this->assertStringContainsString('SUCCESS', $output);
        $this->assertStringContainsString('90%', $output);
        $this->assertFileExists($this->scoreFile);
        $this->assertEquals('90', file_get_contents($this->scoreFile));
    }

    public function testCheckOutputsFailWhenBelowThreshold(): void
    {
        file_put_contents($this->cloverFile, "<?xml version=\"1.0\"?><coverage><project><metrics elements=\"100\" coveredelements=\"10\"/></project></coverage>");

        ob_start();
        CoverageChecker::check(80, false);
        $output = ob_get_clean();

        $this->assertStringContainsString('FAIL', $output);
        $this->assertStringContainsString('10%', $output);
    }

    public function testCheckHandlesExceptionOutput(): void
    {
        if (file_exists($this->cloverFile)) unlink($this->cloverFile);

        ob_start();
        CoverageChecker::check(80, false);
        $output = ob_get_clean();

        $this->assertStringContainsString('ERROR', $output);
        $this->assertStringContainsString('non trouvé', $output);
    }
}