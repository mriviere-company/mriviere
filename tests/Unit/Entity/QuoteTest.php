<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Config\PackageSlug;
use App\Config\QuoteStatus;
use App\Entity\Package;
use App\Entity\Quote;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class QuoteTest extends TestCase
{
    public function testQuoteCopiesPackagePrices(): void
    {
        $package = new Package(PackageSlug::Standard, 'STANDARD');
        $package->setOneShotPriceCents(59900)->setMonthlyPriceCents(4900)->setMaxPages(5);

        $quote = new Quote($package, 'Foo', 'foo@example.com', '0612345678', 'Brief de projet.');

        $this->assertInstanceOf(Uuid::class, $quote->getId());
        $this->assertSame(59900, $quote->getTotalOneShotCents());
        $this->assertSame(4900, $quote->getTotalMonthlyCents());
        $this->assertSame(QuoteStatus::Pending, $quote->getStatus());
    }

    public function testStatusTransitionUpdatesTimestamp(): void
    {
        $package = (new Package(PackageSlug::Starter, 'STARTER'))
            ->setOneShotPriceCents(39900)
            ->setMonthlyPriceCents(2900)
            ->setMaxPages(3);

        $quote = new Quote($package, 'Bar', 'bar@example.com', '0600000000', 'Brief.');
        $initial = $quote->getUpdatedAt();
        usleep(2000);
        $quote->setStatus(QuoteStatus::DepositPaid);

        $this->assertSame(QuoteStatus::DepositPaid, $quote->getStatus());
        $this->assertGreaterThan($initial, $quote->getUpdatedAt());
    }

    public function testStripeIdsAreNullableAndSettable(): void
    {
        $package = (new Package(PackageSlug::Premium, 'PREMIUM'))
            ->setOneShotPriceCents(89900)
            ->setMonthlyPriceCents(7900)
            ->setMaxPages(8);
        $quote = new Quote($package, 'Baz', 'baz@example.com', '0612345678', 'Brief.');

        $this->assertNull($quote->getStripeCheckoutSessionId());
        $quote->setStripeCheckoutSessionId('cs_test_123');
        $this->assertSame('cs_test_123', $quote->getStripeCheckoutSessionId());
    }
}
