<?php

declare(strict_types=1);

namespace App\Service\SuperAdmin;

use App\Entity\ManagedSite;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Thin HTTP wrapper around a managed clone's `/api/super-admin/*` endpoints.
 *
 * Each call signs a fresh short-lived JWT scoped to the operation, then issues
 * the HTTP request. Failure modes (404 opt-out, 401 token issue, network timeout)
 * are mapped to typed result objects rather than thrown — operators always get
 * a status to display, never an unhandled exception.
 */
final readonly class SuperAdminClient
{
    public function __construct(
        private HttpClientInterface $http,
        private JwtSigner $jwtSigner,
    ) {
    }

    public function health(ManagedSite $site, int $timeoutSeconds = 5): HealthResponse
    {
        $token = $this->jwtSigner->sign($site, 'health:read', 60);
        $start = microtime(true);

        try {
            $response = $this->http->request('GET', $site->getBaseUrl() . '/api/super-admin/health', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'timeout' => $timeoutSeconds,
                'max_redirects' => 0,
            ]);

            $status = $response->getStatusCode();
            $latency = (int) round((microtime(true) - $start) * 1000);

            if ($status === 200) {
                try {
                    $body = $response->toArray(false);
                } catch (\Throwable $e) {
                    return new HealthResponse(
                        status: 'invalid_body',
                        httpStatus: $status,
                        latencyMs: $latency,
                        errorReason: 'JSON parse: ' . $e->getMessage(),
                    );
                }

                $observedAt = null;
                if (isset($body['observed_at']) && is_string($body['observed_at'])) {
                    try {
                        $observedAt = new \DateTimeImmutable($body['observed_at']);
                    } catch (\Throwable) {
                    }
                }

                return new HealthResponse(
                    status: 'ok',
                    httpStatus: $status,
                    latencyMs: $latency,
                    appVersion: isset($body['app_version']) ? (string) $body['app_version'] : null,
                    phpVersion: isset($body['php_version']) ? (string) $body['php_version'] : null,
                    databaseOk: isset($body['database_ok']) ? (bool) $body['database_ok'] : null,
                    freeDiskBytes: isset($body['free_disk_bytes']) ? (int) $body['free_disk_bytes'] : null,
                    observedAt: $observedAt,
                );
            }

            return new HealthResponse(
                status: $this->mapHttpStatus($status),
                httpStatus: $status,
                latencyMs: $latency,
                errorReason: $this->extractErrorReason($response),
            );
        } catch (TransportExceptionInterface $e) {
            return new HealthResponse(
                status: 'unreachable',
                httpStatus: null,
                latencyMs: (int) round((microtime(true) - $start) * 1000),
                errorReason: $e->getMessage(),
            );
        } catch (ExceptionInterface $e) {
            return new HealthResponse(
                status: 'http_error',
                httpStatus: null,
                latencyMs: (int) round((microtime(true) - $start) * 1000),
                errorReason: $e->getMessage(),
            );
        }
    }

    /**
     * Aggregated stats for a single day. The clone's response shape is documented
     * in CENTRAL_DASHBOARD_BRIEF.md §6.5: { day, page_views, unique_visitors, top_paths }.
     */
    public function statsSummary(ManagedSite $site, \DateTimeImmutable $day, int $timeoutSeconds = 8): StatsSummary
    {
        $token = $this->jwtSigner->sign($site, 'stats:read', 60);

        try {
            $response = $this->http->request('GET', $site->getBaseUrl() . '/api/super-admin/stats/summary', [
                'query' => ['day' => $day->format('Y-m-d')],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
                'timeout' => $timeoutSeconds,
                'max_redirects' => 0,
            ]);

            $status = $response->getStatusCode();
            if ($status !== 200) {
                return new StatsSummary(status: $this->mapHttpStatus($status), errorReason: $this->extractErrorReason($response));
            }

            $body = $response->toArray(false);
            return new StatsSummary(
                status: 'ok',
                pageViews: (int) ($body['page_views'] ?? 0),
                uniqueVisitors: (int) ($body['unique_visitors'] ?? 0),
                topPaths: is_array($body['top_paths'] ?? null) ? $this->normalizeTopPaths($body['top_paths']) : [],
                day: $day,
            );
        } catch (TransportExceptionInterface $e) {
            return new StatsSummary(status: 'unreachable', errorReason: $e->getMessage());
        } catch (ExceptionInterface $e) {
            return new StatsSummary(status: 'http_error', errorReason: $e->getMessage());
        }
    }

    /**
     * @return list<RemoteAdmin>
     */
    public function listAdmins(ManagedSite $site, int $timeoutSeconds = 8): array
    {
        $token = $this->jwtSigner->sign($site, 'admins:read', 60);

        try {
            $response = $this->http->request('GET', $site->getBaseUrl() . '/api/super-admin/admins', [
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
                'timeout' => $timeoutSeconds,
                'max_redirects' => 0,
            ]);
            if ($response->getStatusCode() !== 200) {
                return [];
            }
            $body = $response->toArray(false);
            $rows = is_array($body['admins'] ?? null) ? $body['admins'] : [];
            return array_values(array_map(fn(array $r) => RemoteAdmin::fromArray($r), $rows));
        } catch (ExceptionInterface) {
            return [];
        }
    }

    /**
     * Creates a remote admin. Returns ['ok' => true, 'admin' => RemoteAdmin, 'temporaryPassword' => '...']
     * on success; ['ok' => false, 'error' => string] otherwise. Note that the temporary password is
     * returned ONCE by the clone — callers must surface it to the human and never log it.
     *
     * @return array{ok: bool, admin?: RemoteAdmin, temporaryPassword?: string, error?: string}
     */
    public function createRemoteAdmin(ManagedSite $site, string $email, int $timeoutSeconds = 8): array
    {
        $token = $this->jwtSigner->sign($site, 'admins:write', 60);

        try {
            $response = $this->http->request('POST', $site->getBaseUrl() . '/api/super-admin/admins', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => ['email' => $email],
                'timeout' => $timeoutSeconds,
                'max_redirects' => 0,
            ]);

            if ($response->getStatusCode() !== 201) {
                return ['ok' => false, 'error' => $this->extractErrorReason($response) ?? 'HTTP ' . $response->getStatusCode()];
            }
            $body = $response->toArray(false);
            return [
                'ok' => true,
                'admin' => RemoteAdmin::fromArray(is_array($body['admin'] ?? null) ? $body['admin'] : []),
                'temporaryPassword' => (string) ($body['temporary_password'] ?? ''),
            ];
        } catch (ExceptionInterface $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** @return array{ok: bool, error?: string} */
    public function setRemoteAdminEnabled(ManagedSite $site, int $adminId, bool $enabled, int $timeoutSeconds = 6): array
    {
        $token = $this->jwtSigner->sign($site, 'admins:write', 60);
        $verb = $enabled ? 'enable' : 'disable';

        try {
            $response = $this->http->request('POST', sprintf('%s/api/super-admin/admins/%d/%s', $site->getBaseUrl(), $adminId, $verb), [
                'headers' => ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'],
                'timeout' => $timeoutSeconds,
                'max_redirects' => 0,
            ]);
            if ($response->getStatusCode() !== 200) {
                return ['ok' => false, 'error' => $this->extractErrorReason($response) ?? 'HTTP ' . $response->getStatusCode()];
            }
            return ['ok' => true];
        } catch (ExceptionInterface $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    /** @param array<int|string, mixed> $raw  @return array<string, int> */
    private function normalizeTopPaths(array $raw): array
    {
        $out = [];
        foreach ($raw as $key => $value) {
            if (is_string($key) && is_int($value)) {
                $out[$key] = $value;
            } elseif (is_array($value) && isset($value['path'], $value['count'])) {
                $out[(string) $value['path']] = (int) $value['count'];
            }
        }
        return $out;
    }

    private function mapHttpStatus(int $code): string
    {
        return match ($code) {
            401 => 'unauthorized',
            403 => 'forbidden',
            404 => 'opt_in_off',
            default => 'http_error',
        };
    }

    private function extractErrorReason(\Symfony\Contracts\HttpClient\ResponseInterface $response): ?string
    {
        try {
            $body = $response->toArray(false);
            if (isset($body['error']) && is_string($body['error'])) {
                return $body['error'];
            }
        } catch (\Throwable) {
        }
        return null;
    }
}
