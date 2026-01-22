<?php

namespace Cogep\PhpUtils;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class CogepPhpUtilsBundle extends AbstractBundle
{
    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // On enregistre toutes les classes de la lib comme services
        // Le chemin './' pointe vers le dossier où se trouve cette classe (src/)
        $container->services()
            ->load('Cogep\\PhpUtils\\', './*')
            ->exclude('./{Tests}')
            ->autowire()
            ->autoconfigure();
    }
}
