<?php

declare(strict_types=1);

namespace App\Entity;

use App\Config\MessageStatus;
use App\Repository\ContactMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactMessageRepository::class)]
#[ORM\Table(name: 'contact_messages')]
class ContactMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 128)]
    private string $name;

    #[ORM\Column(type: 'string', length: 180)]
    private string $email;

    #[ORM\Column(type: 'text')]
    private string $message;

    #[ORM\Column(type: 'string', length: 16, enumType: MessageStatus::class)]
    private MessageStatus $status = MessageStatus::Unread;

    #[ORM\Column(type: 'datetime_immutable', name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $ip = null;

    public function __construct(string $name, string $email, string $message)
    {
        $this->name = $name;
        $this->email = $email;
        $this->message = $message;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getMessage(): string { return $this->message; }
    public function getStatus(): MessageStatus { return $this->status; }
    public function setStatus(MessageStatus $s): self { $this->status = $s; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getIp(): ?string { return $this->ip; }
    public function setIp(?string $v): self { $this->ip = $v; return $this; }
}
