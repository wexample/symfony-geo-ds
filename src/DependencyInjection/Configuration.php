<?php

namespace Wexample\SymfonyGeoDs\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Where the map's tiles and routes come from. The defaults are free and need
 * no key, which suits a demo; the public OSRM server is a demo server and
 * an app in production points `routing.url` at its own, or at a paid one.
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_geo_ds');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('map')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('style_url')
                            ->defaultValue('https://tiles.openfreemap.org/styles/liberty')
                            ->info('MapLibre style json: OpenFreeMap, MapTiler (with its key in the url), or a self-hosted one.')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('routing')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('url')
                            ->defaultValue('https://router.project-osrm.org')
                            ->info('Base url of an OSRM compatible server, asked /route/v1/{profile}/{coordinates}.')
                        ->end()
                        ->scalarNode('profile')
                            ->defaultValue('driving')
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
