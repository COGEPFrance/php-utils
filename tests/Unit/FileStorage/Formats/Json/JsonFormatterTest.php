<?php

namespace Cogep\PhpUtils\Tests\Unit\FileStorage\Formats\Json;

use Cogep\PhpUtils\FileStorage\Enums\FileFormatEnum;
use Cogep\PhpUtils\FileStorage\Formats\Json\JsonFormatter;
use PHPUnit\Framework\TestCase;

class JsonFormatterTest extends TestCase
{
    private JsonFormatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new JsonFormatter();
    }

    public function testGetFileFormat()
    {
        $this->assertSame(FileFormatEnum::JSON, $this->formatter->getFileFormat());
    }

    public function testArrayToRawYieldsValidJsonArray()
    {
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
        $formatterResult = $this->formatter->arrayToRaw($data);
        $result = implode('', iterator_to_array($formatterResult->raw));
        $this->assertJson($result);
        $this->assertSame('[{"id":1,"name":"foo"},{"id":2,"name":"bar"}]', $result);
    }

    public function testArrayToRawYieldsEmptyArrayForEmptyInput()
    {
        $formatterResult = $this->formatter->arrayToRaw([]);
        $result = implode('', iterator_to_array($formatterResult->raw));
        $this->assertSame('[]', $result);
    }

    public function testRawToArrayYieldsDecodedLines()
    {
        $raw = '[{"id":1,"name":"foo"},{"id":2,"name":"bar"}]';
        $result = iterator_to_array($this->formatter->rawToArray($raw));
        $this->assertSame([
            [
                'id' => 1,
                'name' => 'foo',
            ],
            [
                'id' => 2,
                'name' => 'bar',
            ],
        ], $result);
    }

    public function testRawToArraySkipsEmptyLines()
    {
        $raw = "[\n\n{\"id\":1}\n\n]";
        $result = iterator_to_array($this->formatter->rawToArray($raw));
        $this->assertSame([[
            'id' => 1,
        ]], $result);
    }

    public function testRawToArrayThrowsOnInvalidJson()
    {
        $this->expectException(\JsonException::class);
        iterator_to_array($this->formatter->rawToArray('not a json'));
    }
}
