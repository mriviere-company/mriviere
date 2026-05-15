<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ManagedSiteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ManagedSiteRepository::class)]
#[ORM\Table(name: 'managed_sites')]
class ManagedSite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 191, unique: true)]
    private string $domain;

    #[ORM\Column(type: 'string', length: 128)]
    private string $label;

    #[ORM\Column(type: 'string', length: 128, name: 'public_key_fingerprint')]
    private string $publicKeyFingerprint;

    #[ORM\Column(type: 'boolean')]
    private bool $enabled = true;

    #[ORM\Column(type: 'datetime_immutable', name: 'added_at')]
    private \DateTimeImmutable $addedAt;

    #[ORM\Column(type: 'datetime_immutable', name: 'last_seen_at', nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    #[ORM\Column(type: 'string', length: 32, name: 'last_health_status', nullable: true)]
    private ?string $lastHealthStatus = null;

    #[ORM\Column(type: 'text', name: 'last_health_error', nullable: true)]
    private ?string $lastHealthError = null;

    #[ORM\Column(type: 'string', length: 32, name: 'last_app_version', nullable: true)]
    private ?string $lastAppVersion = null;

    public function __construct(string $domain, string $label, string $publicKeyFingerprint)
    {
        $this->domain = $domain;
        $this->label = $label;
        $this->publicKeyFingerprint = $publicKeyFingerprint;
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getDomain(): string { return $this->domain; }
    public function setDomain(string $v): self { $this->domain = $v; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $v): self { $this->label = $v; return $this; }
    public function getPublicKeyFingerprint(): string { return $this->publicKeyFingerprint; }
    public function setPublicKeyFingerprint(string $v): self { $this->publicKeyFingerprint = $v; return $this; }
    public function isEnabled(): bool { return $this->enabled; }
    public function setEnabled(bool $v): self { $this->enabled = $v; return $this; }
    public function getAddedAt(): \DateTimeImmutable { return $this->addedAt; }
    public function getLastSeenAt(): ?\DateTimeImmutable { return $this->lastSeenAt; }
    public function getLastHealthStatus(): ?string { return $this->lastHealthStatus; }
    public function getLastHealthError(): ?string { return $this->lastHealthError; }
    public function getLastAppVersion(): ?string { return $this->lastAppVersion; }

    public function recordHealthOk(string $appVersion): self
    {
        $this->lastSeenAt = new \DateTimeImmutable();
        $this->lastHealthStatus = 'ok';
        $this->lastHealthError = null;
        $this->lastAppVersion = $appVersion;
        return $this;
    }

    public function recordHealthFailure(string $status, string $error): self
    {
        $this->lastSeenAt = new \DateTimeImmutable();
        $this->lastHealthStatus = $status;
        $this->lastHealthError = $error;
        return $this;
    }

    /** Base URL to call this site's API. */
    public function getBaseUrl(): string
    {
        return 'https://' . $this->domain;
    }
}
