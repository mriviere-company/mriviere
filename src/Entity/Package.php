<?php

declare(strict_types=1);

namespace App\Entity;

use App\Config\PackageSlug;
use App\Repository\PackageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PackageRepository::class)]
#[ORM\Table(name: 'packages')]
class Package
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 32, unique: true, enumType: PackageSlug::class)]
    private PackageSlug $slug;

    #[ORM\Column(type: 'string', length: 64)]
    private string $name;

    #[ORM\Column(type: 'integer', name: 'one_shot_price_cents')]
    private int $oneShotPriceCents;

    #[ORM\Column(type: 'integer', name: 'monthly_price_cents')]
    private int $monthlyPriceCents;

    #[ORM\Column(type: 'integer', name: 'max_pages')]
    private int $maxPages;

    /** @var array<int, array{label: string, included: bool}> */
    #[ORM\Column(type: 'json')]
    private array $features = [];

    #[ORM\Column(type: 'string', length: 128, name: 'stripe_monthly_price_id', nullable: true)]
    private ?string $stripeMonthlyPriceId = null;

    #[ORM\Column(type: 'boolean', name: 'is_active')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean', name: 'highlighted')]
    private bool $highlighted = false;

    #[ORM\Column(type: 'integer', name: 'sort_order')]
    private int $sortOrder = 0;

    public function __construct(PackageSlug $slug, string $name)
    {
        $this->slug = $slug;
        $this->name = $name;
    }

    public function getId(): ?int { return $this->id; }
    public function getSlug(): PackageSlug { return $this->slug; }
    public function getName(): string { return $this->name; }
    public function getOneShotPriceCents(): int { return $this->oneShotPriceCents; }
    public function setOneShotPriceCents(int $v): self { $this->oneShotPriceCents = $v; return $this; }
    public function getMonthlyPriceCents(): int { return $this->monthlyPriceCents; }
    public function setMonthlyPriceCents(int $v): self { $this->monthlyPriceCents = $v; return $this; }
    public function getMaxPages(): int { return $this->maxPages; }
    public function setMaxPages(int $v): self { $this->maxPages = $v; return $this; }

    /** @return array<int, array{label: string, included: bool}> */
    public function getFeatures(): array { return $this->features; }

    /** @param array<int, array{label: string, included: bool}> $features */
    public function setFeatures(array $features): self { $this->features = $features; return $this; }

    public function getStripeMonthlyPriceId(): ?string { return $this->stripeMonthlyPriceId; }
    public function setStripeMonthlyPriceId(?string $id): self { $this->stripeMonthlyPriceId = $id; return $this; }
    public function isActive(): bool { return $this->isActive; }
    public function setActive(bool $v): self { $this->isActive = $v; return $this; }
    public function isHighlighted(): bool { return $this->highlighted; }
    public function setHighlighted(bool $v): self { $this->highlighted = $v; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $v): self { $this->sortOrder = $v; return $this; }
}
