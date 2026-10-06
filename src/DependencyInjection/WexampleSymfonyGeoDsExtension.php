<?php

namespace Wexample\SymfonyGeoDs\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;

class WexampleSymfonyGeoDsExtension extends AbstractWexampleSymfonyExtension
{
    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $this->loadConfig(
            __DIR__,
            $container
        );

        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setParameter('wexample_symfony_geo_ds.map.style_url', $config['map']['style_url']);
        $container->setParameter('wexample_symfony_geo_ds.routing.url', $config['routing']['url']);
        $container->setParameter('wexample_symfony_geo_ds.routing.profile', $config['routing']['profile']);
    }
}
