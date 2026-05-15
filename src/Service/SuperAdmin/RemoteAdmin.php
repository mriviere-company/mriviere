<?php

declare(strict_types=1);

namespace App\Service\SuperAdmin;

/** A row returned by `GET /api/super-admin/admins` on a managed clone. */
final readonly class RemoteAdmin
{
    public function __construct(
        public int $id,
        public string $email,
        public bool $enabled,
        public ?\DateTimeImmutable $lastLoginAt,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromArray(array $payload): self
    {
        return new self(
            id: (int) ($payload['id'] ?? 0),
            email: (string) ($payload['email'] ?? ''),
            enabled: (bool) ($payload['enabled'] ?? false),
            lastLoginAt: isset($payload['last_login_at']) && is_string($payload['last_login_at'])
                ? new \DateTimeImmutable($payload['last_login_at']) : null,
            createdAt: isset($payload['created_at']) && is_string($payload['created_at'])
                ? new \DateTimeImmutable($payload['created_at']) : new \DateTimeImmutable(),
        );
    }
}
