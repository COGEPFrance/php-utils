<?php

namespace Cogep\PhpUtils\Tests\Unit\FileStorage\Formats\Csv;

use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
use Cogep\PhpUtils\FileStorage\Formats\Csv\CsvFormatter;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CsvFormatterTest extends TestCase
{
    private $logger;

    private $formatter;

    protected function setUp(): void
    {
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->formatter = new CsvFormatter($this->logger);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetFileFormat()
    {
        $this->assertSame(FileFormatEnum::CSV, $this->formatter->getFileFormat());
    }

    public function testRawToArrayParsesCsv()
    {
        $csv = "id;name\n1;foo\n2;bar\n";
        $result = iterator_to_array($this->formatter->rawToArray($csv));
        $this->assertSame([
            [
                'id' => '1',
                'name' => 'foo',
            ],
            [
                'id' => '2',
                'name' => 'bar',
            ],
        ], $result);
    }

    public function testRawToArrayThrowsOnMalformed()
    {
        $this->expectException(\RuntimeException::class);
        iterator_to_array($this->formatter->rawToArray(''));
    }

    public function testArrayToRawYieldsCsvWithWarmup()
    {
        $this->logger->shouldReceive('debug')
            ->atLeast()
            ->once();
        $data = [
            [
                'id' => 1,
                'name' => 'foo',
            ],
            [
                'id' => 2,
                'name' => 'bar',
            ],
        ];
        $result = $this->formatter->arrayToRaw($data, 1);
        $lines = iterator_to_array($result->raw, false);
        $this->assertStringContainsString('id;name', $lines[0]);
        $this->assertStringContainsString('1;foo', $lines[1]);
        $this->assertStringContainsString('2;bar', $lines[2]);
    }

    public function testArrayToRawYieldsCsvWithoutWarmup()
    {
        $this->logger->shouldReceive('info')
            ->once();
        $this->logger->shouldReceive('debug')
            ->atLeast()
            ->once();
        $data = [
            [
                'id' => 1,
                'name' => 'foo',
            ],
            [
                'id' => 2,
                'name' => 'bar',
            ],
        ];
        $result = $this->formatter->arrayToRaw($data, 10);
        $lines = iterator_to_array($result->raw, false);
        $this->assertStringContainsString('id;name', $lines[0]);
        $this->assertStringContainsString('1;foo', $lines[1]);
        $this->assertStringContainsString('2;bar', $lines[2]);
    }

    public function testFormatValueHandlesTypes()
    {
        $ref = new \ReflectionClass($this->formatter);
        $method = $ref->getMethod('formatValue');

        $this->assertSame('1', $method->invoke($this->formatter, true));
        $this->assertSame('0', $method->invoke($this->formatter, false));
        $this->assertSame('foo', $method->invoke($this->formatter, 'foo'));
        $this->assertSame('{"a":1}', $method->invoke($this->formatter, [
            'a' => 1,
        ]));
        $dt = new \DateTimeImmutable('2020-01-01 12:00:00');
        $this->assertSame('2020-01-01 12:00:00', $method->invoke($this->formatter, $dt));
    }
}
