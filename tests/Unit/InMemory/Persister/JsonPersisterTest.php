<?php

namespace Unit\InMemory\Persister;

use Cogep\PhpUtils\InMemory\Json\JsonPersister;
use Cogep\PhpUtils\InMemory\NoDatasToSaveException;
use Cogep\PhpUtils\Tests\Classes\DummyDynamicEntity;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class JsonPersisterTest extends TestCase
{
    private string $tempDir;

    private JsonPersister $persister;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/json_test_' . uniqid();
        $logger = $this->createMock(LoggerInterface::class);

        $this->persister = new JsonPersister($this->tempDir, $logger);
        parent::setUp();
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

    public function testSaveSuccess(): void
    {
        $entity = \Mockery::mock(DummyDynamicEntity::class, \JsonSerializable::class);
        $entity->shouldReceive('jsonSerialize')
            ->once()
            ->andReturn([
                'id' => 1,
                'name' => 'Data',
            ]);

        $datas = [$entity];
        $filename = 'export.json';

        $result = $this->persister->save($filename, $datas);

        $fullPath = $this->tempDir . '/artefacts/' . $filename;
        $this->assertFileExists($fullPath);
        $this->assertJsonStringEqualsJsonString(
            json_encode([[
                'id' => 1,
                'name' => 'Data',
            ]]),
            file_get_contents($fullPath)
        );
        $this->assertSame($filename, $result->resource);
        $this->assertSame(1, $result->count);
    }

    public function testSaveThrowsExceptionWhenDatasEmpty(): void
    {
        $this->expectException(NoDatasToSaveException::class);

        $this->persister->save('empty.json', []);
    }
}
