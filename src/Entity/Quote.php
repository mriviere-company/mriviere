<?php

declare(strict_types=1);

namespace App\Entity;

use App\Config\QuoteStatus;
use App\Repository\QuoteRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
#[ORM\Table(name: 'quotes')]
class Quote
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: Package::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Package $package;

    #[ORM\Column(type: 'string', length: 128, name: 'client_name')]
    private string $clientName;

    #[ORM\Column(type: 'string', length: 128, name: 'client_company', nullable: true)]
    private ?string $clientCompany = null;

    #[ORM\Column(type: 'string', length: 180, name: 'client_email')]
    private string $clientEmail;

    #[ORM\Column(type: 'string', length: 32, name: 'client_phone')]
    private string $clientPhone;

    #[ORM\Column(type: 'string', length: 255, name: 'client_address', nullable: true)]
    private ?string $clientAddress = null;

    #[ORM\Column(type: 'text', name: 'project_description')]
    private string $projectDescription;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json', name: 'options_json')]
    private array $options = [];

    #[ORM\Column(type: 'integer', name: 'total_one_shot_cents')]
    private int $totalOneShotCents;

    #[ORM\Column(type: 'integer', name: 'total_monthly_cents')]
    private int $totalMonthlyCents;

    #[ORM\Column(type: 'string', length: 32, enumType: QuoteStatus::class)]
    private QuoteStatus $status = QuoteStatus::Pending;

    #[ORM\Column(type: 'string', length: 128, name: 'stripe_customer_id', nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: 'string', length: 128, name: 'stripe_checkout_session_id', nullable: true)]
    private ?string $stripeCheckoutSessionId = null;

    #[ORM\Column(type: 'string', length: 128, name: 'stripe_subscription_id', nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: 'string', length: 5, options: ['default' => 'fr'])]
    private string $locale = 'fr';

    public function __construct(Package $package, string $clientName, string $clientEmail, string $clientPhone, string $projectDescription)
    {
        $this->id = Uuid::v7();
        $this->package = $package;
        $this->clientName = $clientName;
        $this->clientEmail = $clientEmail;
        $this->clientPhone = $clientPhone;
        $this->projectDescription = $projectDescription;
        $this->totalOneShotCents = $package->getOneShotPriceCents();
        $this->totalMonthlyCents = $package->getMonthlyPriceCents();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): Uuid { return $this->id; }
    public function getPackage(): Package { return $this->package; }
    public function getClientName(): string { return $this->clientName; }
    public function getClientCompany(): ?string { return $this->clientCompany; }
    public function setClientCompany(?string $v): self { $this->clientCompany = $v; return $this; }
    public function getClientEmail(): string { return $this->clientEmail; }
    public function getClientPhone(): string { return $this->clientPhone; }
    public function getClientAddress(): ?string { return $this->clientAddress; }
    public function setClientAddress(?string $v): self { $this->clientAddress = $v; return $this; }
    public function getProjectDescription(): string { return $this->projectDescription; }

    /** @return array<string, mixed> */
    public function getOptions(): array { return $this->options; }

    /** @param array<string, mixed> $v */
    public function setOptions(array $v): self { $this->options = $v; return $this; }

    public function getTotalOneShotCents(): int { return $this->totalOneShotCents; }
    public function setTotalOneShotCents(int $v): self { $this->totalOneShotCents = $v; return $this; }
    public function getTotalMonthlyCents(): int { return $this->totalMonthlyCents; }
    public function setTotalMonthlyCents(int $v): self { $this->totalMonthlyCents = $v; return $this; }

    public function getStatus(): QuoteStatus { return $this->status; }
    public function setStatus(QuoteStatus $s): self
    {
        $this->status = $s;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStripeCustomerId(): ?string { return $this->stripeCustomerId; }
    public function setStripeCustomerId(?string $v): self { $this->stripeCustomerId = $v; return $this; }
    public function getStripeCheckoutSessionId(): ?string { return $this->stripeCheckoutSessionId; }
    public function setStripeCheckoutSessionId(?string $v): self { $this->stripeCheckoutSessionId = $v; return $this; }
    public function getStripeSubscriptionId(): ?string { return $this->stripeSubscriptionId; }
    public function setStripeSubscriptionId(?string $v): self { $this->stripeSubscriptionId = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $v): self { $this->ip = $v; return $this; }
    public function getLocale(): string { return $this->locale; }
    public function setLocale(string $v): self { $this->locale = in_array($v, ['fr', 'en'], true) ? $v : 'fr'; return $this; }
}
