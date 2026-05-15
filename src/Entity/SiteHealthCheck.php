<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteHealthCheckRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteHealthCheckRepository::class)]
#[ORM\Table(name: 'site_health_checks')]
#[ORM\Index(columns: ['site_id', 'checked_at'], name: 'IDX_health_site_time')]
class SiteHealthCheck
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ManagedSite::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ManagedSite $site;

    #[ORM\Column(type: 'string', length: 32)]
    private string $status;

    #[ORM\Column(type: 'integer', name: 'latency_ms', nullable: true)]
    private ?int $latencyMs = null;

    #[ORM\Column(type: 'string', length: 32, name: 'app_version', nullable: true)]
    private ?string $appVersion = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $error = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'checked_at')]
    private \DateTimeImmutable $checkedAt;

    public function __construct(ManagedSite $site, string $status)
    {
        $this->site = $site;
        $this->status = $status;
        $this->checkedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getSite(): ManagedSite { return $this->site; }
    public function getStatus(): string { return $this->status; }
    public function getLatencyMs(): ?int { return $this->latencyMs; }
    public function setLatencyMs(?int $v): self { $this->latencyMs = $v; return $this; }
    public function getAppVersion(): ?string { return $this->appVersion; }
    public function setAppVersion(?string $v): self { $this->appVersion = $v; return $this; }
    public function getError(): ?string { return $this->error; }
    public function setError(?string $v): self { $this->error = $v; return $this; }
    public function getCheckedAt(): \DateTimeImmutable { return $this->checkedAt; }

    public function isOk(): bool { return $this->status === 'ok'; }
}
