<?php

namespace Cogep\PhpUtils\Tests\Unit\FileStorage;

use Cogep\PhpUtils\FileStorage\Destinations\Local\LocalStorageDestinationPort;
use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
use Cogep\PhpUtils\FileStorage\Enums\FileStorageDestinationEnum;
use Cogep\PhpUtils\FileStorage\FileStorageFactory;
use Cogep\PhpUtils\FileStorage\PersisterResultEntity;
use Cogep\PhpUtils\FileStorage\Ports\FileDestinationPort;
use Cogep\PhpUtils\FileStorage\Ports\FileFormatterPort;
use Cogep\PhpUtils\FileStorage\Ports\FileFormatterWithWarmupLimitInterface;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FileStorageFactoryTest extends TestCase
{
    private $localStorage;

    private $formatter;

    private $logger;

    protected function setUp(): void
    {
        $this->localStorage = Mockery::mock(LocalStorageDestinationPort::class);
        $this->formatter = Mockery::mock(FileFormatterWithWarmupLimitInterface::class, FileFormatterPort::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->formatter->shouldReceive('getFileFormat')
            ->andReturn(FileFormatEnum::CSV);
        $this->localStorage->shouldReceive('getDestination')
            ->andReturn(FileStorageDestinationEnum::LOCAL);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testWriteLocalStorage()
    {
        $this->formatter->shouldReceive('arrayToRaw')
            ->andReturn((function () {
                yield 'foo';
            })());
        $this->localStorage->shouldReceive('saveRawFileFromGenerator')
            ->once();
        $factory = new FileStorageFactory([$this->localStorage], [$this->formatter], $this->logger);
        $this->logger->shouldReceive('info')
            ->twice();

        $result = $factory->write('test.csv', [[
            'a' => 1,
        ]], 10);
        $this->assertInstanceOf(PersisterResultEntity::class, $result);
        $this->assertSame('test.csv', $result->resource);
        $this->assertSame(1, $result->count);
    }

    public function testWriteRemoteStorage()
    {
        $remote = Mockery::mock(FileDestinationPort::class);
        $remote->shouldReceive('getDestination')
            ->andReturn(FileStorageDestinationEnum::AZURE);
        $remote->shouldReceive('saveRawFile')
            ->once();

        $this->formatter->shouldReceive('arrayToRaw')
            ->andReturn((function () {
                yield 'foo';
            })());
        $this->localStorage->shouldReceive('getDestination')
            ->andReturn(FileStorageDestinationEnum::LOCAL);
        $this->localStorage->shouldReceive('saveRawFileFromGenerator')
            ->once();
        $this->localStorage->shouldReceive('fetchRawFile')
            ->andReturn('foo');
        $this->logger->shouldReceive('info')
            ->twice();

        $factory = new FileStorageFactory([$this->localStorage, $remote], [$this->formatter], $this->logger);

        $result = $factory->write('azure://container/file.csv', [[
            'a' => 1,
        ]]);
        $this->assertInstanceOf(PersisterResultEntity::class, $result);
    }

    public function testWriteThrowsOnUnknownFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $factory = new FileStorageFactory([$this->localStorage], [$this->formatter], $this->logger);
        $factory->write('test.unknown', []);
    }

    public function testWriteThrowsOnUnknownDestination()
    {
        $this->expectException(\InvalidArgumentException::class);

        $remote = Mockery::mock(FileDestinationPort::class);
        $remote->shouldReceive('getDestination')
            ->andReturn(FileStorageDestinationEnum::AZURE);

        $this->formatter->shouldReceive('arrayToRaw')
            ->andReturn((function () {
                yield 'foo';
            })());
        $this->localStorage->shouldReceive('getDestination')
            ->andReturn(FileStorageDestinationEnum::LOCAL);
        $this->logger->shouldReceive('info')
            ->twice();

        $factory = new FileStorageFactory([$this->localStorage], [$this->formatter], $this->logger);
        $factory->write('azure://container/file.csv', [[
            'a' => 1,
        ]]);
    }

    public function testStripPrefixRemovesKnownPrefix()
    {
        $factory = new FileStorageFactory([$this->localStorage], [$this->formatter], $this->logger);
        $ref = new \ReflectionClass($factory);
        $method = $ref->getMethod('stripPrefix');

        $result = $method->invoke($factory, 'azure://container/file.csv');
        $this->assertSame('container/file.csv', $result);
    }

    public function testWriteWithFormatterWithoutWarmup()
    {
        $formatter = Mockery::mock(FileFormatterPort::class);
        $formatter->shouldReceive('getFileFormat')
            ->andReturn(FileFormatEnum::CSV);
        $formatter->shouldReceive('arrayToRaw')
            ->andReturn((function () {
                yield 'foo';
            })());
        $this->localStorage->shouldReceive('saveRawFileFromGenerator')
            ->once();
        $this->logger->shouldReceive('info')
            ->twice();

        $factory = new FileStorageFactory([$this->localStorage], [$formatter], $this->logger);
        $result = $factory->write('test.csv', [[
            'a' => 1,
        ]], 10);
        $this->assertInstanceOf(PersisterResultEntity::class, $result);
    }

    public function testResolveDestinationReturnsExpectedEnum()
    {
        $factory = new FileStorageFactory([$this->localStorage], [$this->formatter], $this->logger);
        $ref = new \ReflectionClass($factory);
        $method = $ref->getMethod('resolveDestination');
        $result = $method->invoke($factory, 'azure://container/file.csv');
        $this->assertSame(FileStorageDestinationEnum::AZURE, $result);
        $result2 = $method->invoke($factory, 'test.csv');
        $this->assertSame(FileStorageDestinationEnum::LOCAL, $result2);
    }

    public function testConstructorThrowsIfNoLocalStorage()
    {
        $this->expectException(\LogicException::class);
        $formatter = Mockery::mock(FileFormatterWithWarmupLimitInterface::class, FileFormatterPort::class);
        $formatter->shouldReceive('getFileFormat')
            ->andReturn(FileFormatEnum::CSV);
        $logger = Mockery::mock(LoggerInterface::class);

        new FileStorageFactory([], [$formatter], $logger);
    }
}
