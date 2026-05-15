<?php

declare(strict_types=1);

namespace App\Service\SuperAdmin;

/**
 * Result of a `/api/super-admin/health` call.
 *
 * `status` carries the operational reading we report to the operator :
 *   - 'ok'             clone responded 200 with a healthy body
 *   - 'unreachable'    network/DNS/timeout — clone may be offline
 *   - 'unauthorized'   401 — JWT contract issue (clock drift, key mismatch…)
 *   - 'forbidden'      403 — clone enforces a scope we didn't request
 *   - 'opt_in_off'     404 — clone is up but super-admin is disabled (anti-fingerprint)
 *   - 'http_error'     other HTTP status
 *   - 'invalid_body'   200 but body didn't match the contract
 */
final readonly class HealthResponse
{
    public function __construct(
        public string $status,
        public ?int $httpStatus,
        public ?int $latencyMs,
        public ?string $appVersion = null,
        public ?string $phpVersion = null,
        public ?bool $databaseOk = null,
        public ?int $freeDiskBytes = null,
        public ?\DateTimeImmutable $observedAt = null,
        public ?string $errorReason = null,
    ) {
    }

    public function isOk(): bool
    {
        return $this->status === 'ok';
    }
}
