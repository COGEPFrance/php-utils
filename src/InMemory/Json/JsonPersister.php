<?php


namespace Cogep\PhpUtils\InMemory\Json;

use Cogep\PhpUtils\Classes\EntityInterface;
use Cogep\PhpUtils\InMemory\NoDatasToSaveException;
use Cogep\PhpUtils\InMemory\PersisterResultEntity;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;


/**
 * @template T of EntityInterface
 */
class JsonPersister
{
    public const string DESTINATION_DIR = 'artefacts';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private readonly string          $projectDir,
        private readonly LoggerInterface $logger,
    )
    {
    }

    /**
     * @param string $filename
     * @param iterable<T> $datas
     * @return PersisterResultEntity
     * @throws NoDatasToSaveException
     * @throws \JsonException
     */
    public function save(string $filename, iterable $datas): PersisterResultEntity
    {
        $arrayData = iterator_to_array($datas);

        if (empty($arrayData)) {
            throw new NoDatasToSaveException();
        }
        $fullPath = $this->getFullPath($filename);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($fullPath, json_encode($arrayData, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        $this->logger->info('Fichier JSON généré', [
            'filename' => $filename,
            'count' => count($arrayData),
        ]);

        return new PersisterResultEntity($filename, count($arrayData));
    }

    private function getFullPath(string $filename): string
    {
        return sprintf('%s/%s/%s', $this->projectDir, self::DESTINATION_DIR, $filename);
    }
}
