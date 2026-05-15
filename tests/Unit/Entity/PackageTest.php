<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Config\PackageSlug;
use App\Entity\Package;
use PHPUnit\Framework\TestCase;

final class PackageTest extends TestCase
{
    public function testNewPackageStoresSlugAndName(): void
    {
        $p = new Package(PackageSlug::Starter, 'STARTER');
        $this->assertSame(PackageSlug::Starter, $p->getSlug());
        $this->assertSame('STARTER', $p->getName());
        $this->assertTrue($p->isActive());
        $this->assertFalse($p->isHighlighted());
    }

    public function testFluentSetters(): void
    {
        $p = new Package(PackageSlug::Standard, 'STANDARD');
        $p->setOneShotPriceCents(59900)
            ->setMonthlyPriceCents(4900)
            ->setMaxPages(5)
            ->setHighlighted(true)
            ->setSortOrder(2);

        $this->assertSame(59900, $p->getOneShotPriceCents());
        $this->assertSame(4900, $p->getMonthlyPriceCents());
        $this->assertSame(5, $p->getMaxPages());
        $this->assertTrue($p->isHighlighted());
        $this->assertSame(2, $p->getSortOrder());
    }

    public function testFeaturesArrayShape(): void
    {
        $p = new Package(PackageSlug::Premium, 'PREMIUM');
        $features = [
            ['label' => 'Galerie photos', 'included' => true],
            ['label' => 'Blog', 'included' => true],
        ];
        $p->setFeatures($features);
        $this->assertSame($features, $p->getFeatures());
    }
}
