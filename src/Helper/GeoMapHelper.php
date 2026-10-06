<?php

namespace Wexample\SymfonyGeoDs\Helper;

use InvalidArgumentException;
use Stringable;
use Wexample\SymfonyGeo\Class\GeoPoint;
use Wexample\SymfonyGeo\Helper\PostalAddressHelper;
use Wexample\SymfonyGeo\Interface\GeoLocatedInterface;
use Wexample\SymfonyGeo\Interface\PostalAddressInterface;

class GeoMapHelper
{
    /**
     * The pins a map is handed, from what a template has at hand: located
     * entities, points, or arrays { point | lat + lng, label, href }. What is
     * not located yet has no place on the map and is left out.
     *
     * @return list<array{lat: float, lng: float, label: string|null, href: string|null}>
     */
    public static function markers(iterable $items): array
    {
        $markers = [];

        foreach ($items as $item) {
            $marker = is_array($item) ? self::markerFromArray($item) : self::markerFrom($item, null, null);

            if (null !== $marker) {
                $markers[] = $marker;
            }
        }

        return $markers;
    }

    private static function markerFromArray(array $item): ?array
    {
        $label = $item['label'] ?? null;
        $href = $item['href'] ?? null;

        if (isset($item['point'])) {
            return self::markerFrom($item['point'], $label, $href);
        }

        if (isset($item['lat'], $item['lng'])) {
            return self::markerFrom(new GeoPoint((float) $item['lat'], (float) $item['lng']), $label, $href);
        }

        throw new InvalidArgumentException('A map marker array needs a "point", or "lat" and "lng".');
    }

    private static function markerFrom(
        mixed $item,
        ?string $label,
        ?string $href
    ): ?array {
        if ($item instanceof GeoLocatedInterface) {
            $label ??= self::labelOf($item);
            $item = $item->getGeoPoint();

            if (null === $item) {
                return null;
            }
        }

        if (! $item instanceof GeoPoint) {
            throw new InvalidArgumentException(sprintf('A map marker cannot be made of %s.', get_debug_type($item)));
        }

        return [
            'lat' => $item->latitude,
            'lng' => $item->longitude,
            'label' => $label,
            'href' => $href,
        ];
    }

    private static function labelOf(GeoLocatedInterface $item): ?string
    {
        if ($item instanceof PostalAddressInterface) {
            return PostalAddressHelper::toInline($item) ?: null;
        }

        return $item instanceof Stringable ? (string) $item : null;
    }
}
