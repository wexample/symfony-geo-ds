<?php

namespace Wexample\SymfonyGeoDs\Twig;

use Twig\Environment;
use Twig\TwigFunction;
use Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension;
use Wexample\SymfonyGeoDs\Helper\GeoMapHelper;
use Wexample\SymfonyLoader\Twig\ComponentsExtension;

/**
 * A map with pins: `geo_map(items, { route: true, height: '24rem' })`, items
 * being located entities, points or arrays (GeoMapHelper::markers). With
 * `route`, the pins are joined by road, in the order given.
 */
class GeoMapExtension extends AbstractTemplateExtension
{
    public function __construct(
        ComponentsExtension $componentsExtension,
        private readonly string $styleUrl,
        private readonly string $routingUrl,
        private readonly string $routingProfile,
    ) {
        parent::__construct($componentsExtension);
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'geo_map',
                function (
                    Environment $twig,
                    $context,
                    iterable $items,
                    array $options = []
                ): string {
                    return $this->renderComponent(
                        $twig,
                        $context,
                        '@WexampleSymfonyGeoDsBundle/components/geo-map',
                        [
                            'height' => $options['height'] ?? null,
                            'class' => $options['class'] ?? null,
                        ],
                        [
                            'markers' => GeoMapHelper::markers($items),
                            'route' => (bool) ($options['route'] ?? false),
                            // Where the map opens when it has no pin to frame.
                            'center' => $options['center'] ?? null,
                            'zoom' => $options['zoom'] ?? null,
                            'styleUrl' => $options['style_url'] ?? $this->styleUrl,
                            'routingUrl' => $this->routingUrl,
                            'routingProfile' => $options['routing_profile'] ?? $this->routingProfile,
                        ]
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }
}
