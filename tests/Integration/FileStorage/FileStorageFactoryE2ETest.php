<?php

namespace Cogep\PhpUtils\Tests\Integration\FileStorage;

use Cogep\PhpUtils\FileStorage\Destinations\Local\LocalStorageDestinationPort;
use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;
use Cogep\PhpUtils\FileStorage\FileStorageFactory;
use Cogep\PhpUtils\FileStorage\Formats\Csv\CsvFormatter;
use Cogep\PhpUtils\FileStorage\PersisterResultEntity;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FileStorageFactoryE2ETest extends TestCase
{
    public function testE2EWriteCsvFile()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'csvtest_');
        $filename = basename($tmpFile) . '.csv';
        $csvPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($tmpFile)) {
            unlink($tmpFile);
        }
        if (file_exists($csvPath)) {
            unlink($csvPath);
        }

        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')
            ->atLeast()
            ->once();
        $logger->shouldReceive('debug')
            ->atLeast()
            ->once();

        $formatter = new CsvFormatter($logger);

        $localStorage = new class($csvPath) implements LocalStorageDestinationPort {
            private $file;

            public function __construct($file)
            {
                $this->file = $file;
            }

            public function getDestination(): FileStorageDestinationEnum
            {
                return FileStorageDestinationEnum::LOCAL;
            }

            public function saveRawFileFromGenerator($path, $rawGenerator): void
            {
                $f = fopen($this->file, 'w');
                foreach ($rawGenerator as $line) {
                    fwrite($f, $line);
                }
                fclose($f);
            }

            public function fetchRawFile($path): string
            {
                return file_get_contents($this->file);
            }

            public function saveRawFile(string $path, string $rawFile): void
            {

            }
        };

        $factory = new FileStorageFactory([$localStorage], [$formatter], $logger);

        $data = [
            [
                'col1' => 'a',
                'col2' => 'b',
            ],
            [
                'col1' => 'c',
                'col2' => 'd',
            ],
        ];
        $result = $factory->write($filename, $data);

        $this->assertInstanceOf(PersisterResultEntity::class, $result);
        $this->assertSame($filename, $result->resource);
        $this->assertSame(2, $result->count);
        $content = file_get_contents($csvPath);
        $this->assertStringContainsString('a;b', $content);
        $this->assertStringContainsString('c;d', $content);

        unlink($csvPath);
    }
}
