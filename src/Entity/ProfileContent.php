<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProfileContentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfileContentRepository::class)]
#[ORM\Table(name: 'profile_content')]
class ProfileContent
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private int $id = 1;

    #[ORM\Column(type: 'text')]
    private string $bio = '';

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $stack = [];

    /** @var array<string, string> */
    #[ORM\Column(type: 'json')]
    private array $links = [];

    #[ORM\Column(type: 'string', length: 255, name: 'photo_url', nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(type: 'datetime_immutable', name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getBio(): string { return $this->bio; }
    public function setBio(string $bio): self
    {
        $this->bio = $bio;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /** @return list<string> */
    public function getStack(): array { return $this->stack; }

    /** @param list<string> $stack */
    public function setStack(array $stack): self
    {
        $this->stack = $stack;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    /** @return array<string, string> */
    public function getLinks(): array { return $this->links; }

    /** @param array<string, string> $links */
    public function setLinks(array $links): self
    {
        $this->links = $links;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getPhotoUrl(): ?string { return $this->photoUrl; }
    public function setPhotoUrl(?string $url): self
    {
        $this->photoUrl = $url;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
