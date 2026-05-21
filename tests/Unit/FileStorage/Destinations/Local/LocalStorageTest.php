<?php

namespace Cogep\PhpUtils\Tests\Unit\FileStorage\Destinations\Local;

use Cogep\PhpUtils\FileStorage\Destinations\Local\LocalStorage;
use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LocalStorageTest extends TestCase
{
    private $storage;

    private $tmpFile;

    private $tmpDir;

    protected function setUp(): void
    {
        $this->storage = new LocalStorage();
        $this->tmpDir = sys_get_temp_dir() . '/local_storage_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
        $this->tmpFile = $this->tmpDir . '/test.txt';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
    }

    public function testGetDestinationReturnsLocalEnum()
    {
        $this->assertSame(FileStorageDestinationEnum::LOCAL, $this->storage->getDestination());
    }

    public function testSaveRawFileAndFetchRawFile()
    {
        $data = 'foo bar';
        $this->storage->saveRawFile($this->tmpFile, $data);
        $this->assertFileExists($this->tmpFile);
        $this->assertSame($data, $this->storage->fetchRawFile($this->tmpFile));
    }

    public function testFetchRawFileThrowsIfNotExists()
    {
        $this->expectException(RuntimeException::class);
        $this->storage->fetchRawFile($this->tmpDir . '/notfound.txt');
    }

    public function testSaveRawFileThrowsOnError()
    {
        $this->expectException(\RuntimeException::class);

        $dir = sys_get_temp_dir() . '/unwritable_' . uniqid();
        @mkdir($dir, 0555);
        $file = $dir . '/file.txt';

        // On remplace temporairement le gestionnaire d'erreurs pour ignorer les warnings PHP natifs
        set_error_handler(function ($errno, $errstr) {
            return ($errno === E_WARNING) ? true : false;
        });

        try {
            $this->storage->saveRawFile($file, 'data');
        } finally {
            restore_error_handler(); // Toujours restaurer le gestionnaire initial
            @chmod($dir, 0755);
            @rmdir($dir);
        }
    }

    public function testSaveRawFileFromGenerator()
    {
        $lines = ['a', 'b', 'c'];
        $generator = (function () use ($lines) {
            foreach ($lines as $line) {
                yield $line . PHP_EOL;
            }
        })();

        $this->storage->saveRawFileFromGenerator($this->tmpFile, $generator);
        $this->assertFileExists($this->tmpFile);
        $this->assertSame("a\nb\nc\n", file_get_contents($this->tmpFile));
    }

    public function testSaveRawFileFromGeneratorThrowsOnError()
    {
        $this->expectException(\RuntimeException::class);

        $dir = sys_get_temp_dir() . '/unwritable_' . uniqid();
        @mkdir($dir, 0555);
        $file = $dir . '/file.txt';

        set_error_handler(function ($errno, $errstr) {
            return ($errno === E_WARNING) ? true : false;
        });

        try {
            $generator = (function () {
                yield 'foo';
            })();
            $this->storage->saveRawFileFromGenerator($file, $generator);
        } finally {
            restore_error_handler();
            @chmod($dir, 0755);
            @rmdir($dir);
        }
    }

    public function testSaveRawFileThrowsIfDirectoryCannotBeCreated()
    {
        $this->expectException(\RuntimeException::class);

        $dir = sys_get_temp_dir() . '/not_a_dir_' . uniqid();
        @file_put_contents($dir, 'not a dir');
        $file = $dir . '/file.txt';

        set_error_handler(function ($errno, $errstr) {
            return ($errno === E_WARNING) ? true : false;
        });

        try {
            $this->storage->saveRawFile($file, 'data');
        } finally {
            restore_error_handler();
            @unlink($dir);
        }
    }
}
