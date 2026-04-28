<?php

namespace Unit\InMemory\Fetcher;

use Cogep\PhpUtils\InMemory\Csv\CsvFetcher;
use PHPUnit\Framework\TestCase;

class CsvFetcherTest extends TestCase
{
    private string $tempDir;

    private string $csvPath;

    private CsvFetcher $fetcher;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/fetcher_test_' . uniqid();
        $this->csvPath = $this->tempDir . '/' . CsvFetcher::SOURCE_FILE;

        if (! is_dir($this->csvPath)) {
            mkdir($this->csvPath, 0777, true);
        }

        $this->fetcher = new CsvFetcher($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($files as $fileinfo) {
                $fileinfo->isDir() ? rmdir($fileinfo->getRealPath()) : unlink($fileinfo->getRealPath());
            }
            rmdir($this->tempDir);
        }
    }

    public function testFetch(): void
    {
        $filename = 'test.csv';
        $content = "id;name;status\n";
        $content .= "101;John Doe;ACTIVE\n";
        $content .= "102;Jane Doe;INACTIVE\n";
        $content .= "103;Corrupted Line\n";     // Test le array_pad (trop court)
        $content .= "104;Extra;Active;Plus\n";  // Test le array_slice (trop long)
        $content .= ";;\n";                     // Test le continue (ligne vide filtrée)

        file_put_contents($this->csvPath . '/' . $filename, $content);

        $generator = $this->fetcher->fetch($filename);
        $results = iterator_to_array($generator);

        $this->assertCount(4, $results);
        $this->assertEquals('101', $results[0]['id']);
        $this->assertEquals('John Doe', $results[0]['name']);
        $this->assertEquals('ACTIVE', $results[0]['status']);
    }

    public function testFetchThrowsExceptionIfFileMissing(): void
    {
        $this->expectException(\RuntimeException::class);
        iterator_to_array($this->fetcher->fetch('inexistant.csv'));
    }

    public function testFetchReturnsEmptyOnEmptyFile(): void
    {
        $filename = 'empty.csv';
        touch($this->csvPath . '/' . $filename);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Le fichier CSV est vide ou mal formé.');

        $this->fetcher->fetch($filename);
    }
}
