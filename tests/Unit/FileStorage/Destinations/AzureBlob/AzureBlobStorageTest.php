<?php

namespace Cogep\PhpUtils\Tests\Unit\FileStorage\Destinations\AzureBlob;

use Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\AzureBlobStorage;
use Cogep\PhpUtils\FileStorage\Destinations\AzureBlob\Client\AzureBlobClient;
use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;
use Mockery;
use PHPUnit\Framework\TestCase;

class AzureBlobStorageTest extends TestCase
{
    private $client;

    private $storage;

    protected function setUp(): void
    {
        $this->client = Mockery::mock(AzureBlobClient::class);
        $this->storage = new AzureBlobStorage($this->client);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetDestinationReturnsAzureEnum()
    {
        $this->assertSame(FileStorageDestinationEnum::AZURE, $this->storage->getDestination());
    }

    public function testFetchRawFileCallsClientAndReturnsContent()
    {
        $this->client->shouldReceive('getBlob')
            ->with('foo/bar.txt')
            ->once()
            ->andReturn('file-content');

        $result = $this->storage->fetchRawFile('foo/bar.txt');
        $this->assertSame('file-content', $result);
    }

    public function testSaveRawFileCallsClientWithCorrectArgs()
    {
        $this->client->shouldReceive('putBlob')
            ->with('foo/bar.txt', 'data')
            ->once();

        $this->storage->saveRawFile('foo/bar.txt', 'data');
        $this->assertTrue(true);
    }
}
