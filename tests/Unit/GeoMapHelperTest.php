<?php

namespace Wexample\SymfonyGeoDs\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Wexample\SymfonyGeo\Class\GeoPoint;
use Wexample\SymfonyGeo\Entity\AbstractAddress;
use Wexample\SymfonyGeoDs\Helper\GeoMapHelper;

class GeoMapHelperTest extends TestCase
{
    public function testMarkers(): void
    {
        $located = (new class() extends AbstractAddress {
        })
            ->setPostalAddress('Grand-Place')
            ->setPostCode('1000')
            ->setCity('Bruxelles')
            ->setGeoPoint(new GeoPoint(50.8467, 4.3525));
        $notLocated = (new class() extends AbstractAddress {
        })->setCity('Nowhere');

        $this->assertSame([
            ['lat' => 50.8467, 'lng' => 4.3525, 'label' => 'Grand-Place, 1000 Bruxelles', 'href' => null],
            ['lat' => 50.8467, 'lng' => 4.3525, 'label' => 'Town hall', 'href' => '/hall'],
            ['lat' => 48.8566, 'lng' => 2.3522, 'label' => null, 'href' => null],
            ['lat' => 45.0, 'lng' => 5.0, 'label' => 'Typed', 'href' => null],
        ], GeoMapHelper::markers([
            $located,
            $notLocated,
            ['point' => $located, 'label' => 'Town hall', 'href' => '/hall'],
            new GeoPoint(48.8566, 2.3522),
            ['lat' => '45', 'lng' => 5, 'label' => 'Typed'],
        ]));
    }

    public function testRejectsUnknownShapes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GeoMapHelper::markers([['label' => 'no point']]);
    }
}
