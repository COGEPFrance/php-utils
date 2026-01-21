<?php


namespace Unit\InMemory\Fetcher;

use Cogep\PhpUtils\Classes\EntityInterface;
use Cogep\PhpUtils\InMemory\Csv\CsvPersister;
use Cogep\PhpUtils\InMemory\NoDatasToSaveException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CsvPersisterTest extends TestCase
{
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/csv_test_' . uniqid();
        $logger = $this->createMock(LoggerInterface::class);

        $this->persister = new CsvPersister($this->tempDir, $logger);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->tempDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }

            rmdir($this->tempDir);
        }
    }

    public function testSaveDiscoveryWithWarmup(): void
    {
        $dossier1 = new class() implements EntityInterface {
            public int $id = 1;

            public bool $active = true;

            public \DateTimeImmutable $date;
        };
        $dossier1->date = new \DateTimeImmutable('2026-01-01 10:00:00');

        $dossier2 = new class() implements EntityInterface {
            public int $id = 2;

            public string $new_column = 'surprise';
        };

        $result = $this->persister->save('test.csv', [$dossier1, $dossier2], 2);

        $filePath = $this->tempDir . '/' . $this->persister::DESTINATION_DIR . '/' . $result->resource;
        $this->assertFileExists($filePath);

        $content = file($filePath);

        $headers = str_getcsv($content[0], ';', escape: '');
        $this->assertEquals(['id', 'active', 'date', 'new_column'], $headers);

        $row1 = str_getcsv($content[1], ';', escape: '');
        $this->assertEquals('1', $row1[0]);
        $this->assertEquals('1', $row1[1]);
        $this->assertEquals('2026-01-01 10:00:00', $row1[2]);
        $this->assertEquals('', $row1[3]);

        $row2 = str_getcsv($content[2], ';', escape: '');
        $this->assertEquals('2', $row2[0]);
        $this->assertEquals('', $row2[1]);
        $this->assertEquals('', $row2[2]);
        $this->assertEquals('surprise', $row2[3]);
    }

    public function testSaveThrowsExceptionIfNoEntities(): void
    {
        $this->expectException(NoDatasToSaveException::class);
        $this->persister->save('test.csv', []);
    }
}
