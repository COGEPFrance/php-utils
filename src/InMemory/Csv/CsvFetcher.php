<?php

namespace Cogep\PhpUtils\InMemory\Csv;


use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CsvFetcher
{
    public const string SOURCE_FILE = 'assets';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array<array<string,mixed>>
     */
    public function fetch(string $filename): array
    {
        $handle = $this->openFile(basename($filename));

        $headers = fgetcsv($handle, 0, ';', escape: '');

        if (! is_array($headers) || empty(array_filter($headers))) {
            fclose($handle);
            throw new \RuntimeException('Le fichier CSV est vide ou mal formé.');
        }

        /** @var array<string> $headers */
        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $headerCount = count($headers);

        $datas = [];

        try {
            while (($row = fgetcsv($handle, 0, ';', escape: '')) !== false) {
                if (count(array_filter($row)) === 0) {
                    continue;
                }

                $row = $this->processRow($row, $headerCount);

                $datas[] = array_combine($headers, $row);
            }
        } finally {
            fclose($handle);
        }

        return $datas;
    }

    /**
     * @param array<int,mixed> $row
     * @return array<int,mixed>
     */
    private function processRow(array $row, int $headerCount): array
    {
        $rowCount = count($row);

        if ($rowCount !== $headerCount) {
            $row = ($rowCount < $headerCount)
                ? array_pad($row, $headerCount, '')
                : array_slice($row, 0, $headerCount);
        }

        return $row;
    }

    /**
     * @return resource
     */
    private function openFile(string $filename)
    {
        $filePath = sprintf('%s/%s/%s', $this->projectDir, self::SOURCE_FILE, $filename);

        if (! file_exists($filePath) || ! ($handle = fopen($filePath, 'r'))) {
            throw new \RuntimeException("Impossible d'ouvrir le fichier : {$filePath}");
        }

        return $handle;
    }
}
