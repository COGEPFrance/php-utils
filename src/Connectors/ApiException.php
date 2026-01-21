<?php

namespace Cogep\PhpUtils\Connectors;

use Symfony\Contracts\HttpClient\ResponseInterface;

abstract class ApiException extends \Exception
{
    /**
     * @var array<string, mixed>|array<int, array<string, mixed>>|null
     */
    public ?array $details = null;

    public function __construct(ResponseInterface $response, ?\Throwable $previous = null)
    {
        $status = $response->getStatusCode();

        try {
            $data = $response->toArray(false);
            $message = $this->extractErrorMessage($data) ?? $response->getContent(false);
        } catch (\Throwable $e) {
            $data = null;
            $message = "Impossible de lire le corps de la réponse d'erreur.";
        }
        $finalMessage = sprintf('Error %s (%d): %s', $this->getApiName(), $status, $message);

        parent::__construct($finalMessage, $status, $previous);

        $this->details = is_array($data) ? $data : null;
    }

    /**
     * Définition en fonction du format JSON de retour de l'API.
     */
    abstract protected function extractErrorMessage(mixed $data): ?string;

    abstract protected function getApiName(): string;
}
