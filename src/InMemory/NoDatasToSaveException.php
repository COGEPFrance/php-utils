<?php

namespace Cogep\PhpUtils\InMemory;

class NoDatasToSaveException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Aucune entité à sauvegarder', 400);
    }
}
