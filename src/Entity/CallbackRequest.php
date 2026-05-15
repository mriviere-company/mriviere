<?php

declare(strict_types=1);

namespace App\Entity;

use App\Config\MessageStatus;
use App\Repository\CallbackRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallbackRequestRepository::class)]
#[ORM\Table(name: 'callback_requests')]
class CallbackRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 128)]
    private string $name;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'string', length: 32)]
    private string $phone;

    #[ORM\Column(type: 'datetime_immutable', name: 'preferred_slot')]
    private \DateTimeImmutable $preferredSlot;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: 'string', length: 16, enumType: MessageStatus::class)]
    private MessageStatus $status = MessageStatus::Unread;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip = null;

    public function __construct(string $name, string $email, string $phone, \DateTimeImmutable $preferredSlot)
    {
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->preferredSlot = $preferredSlot;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): string { return $this->phone; }
    public function getPreferredSlot(): \DateTimeImmutable { return $this->preferredSlot; }
    public function getMessage(): ?string { return $this->message; }
    public function setMessage(?string $v): self { $this->message = $v; return $this; }
    public function getStatus(): MessageStatus { return $this->status; }
    public function setStatus(MessageStatus $s): self { $this->status = $s; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $v): self { $this->ip = $v; return $this; }
}
