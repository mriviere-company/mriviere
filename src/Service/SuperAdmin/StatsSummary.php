<?php

declare(strict_types=1);

namespace App\Service\SuperAdmin;

/**
 * Result of `/api/super-admin/stats/summary` on a clone.
 *
 * `status === 'ok'` means the clone returned data; otherwise the call failed
 * (network/auth/opt-out) and counts are zero.
 */
final readonly class StatsSummary
{
    public function __construct(
        public string $status,
        public ?string $errorReason = null,
        public int $pageViews = 0,
        public int $uniqueVisitors = 0,
        /** @var array<string, int> */
        public array $topPaths = [],
        public ?\DateTimeImmutable $day = null,
    ) {
    }

    public function isOk(): bool
    {
        return $this->status === 'ok';
    }
}
