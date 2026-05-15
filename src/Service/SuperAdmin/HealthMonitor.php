<?php

declare(strict_types=1);

namespace App\Service\SuperAdmin;

use App\Entity\ManagedSite;
use App\Entity\SiteHealthCheck;
use App\Repository\ManagedSiteRepository;
use App\Repository\SiteHealthCheckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Orchestrates health-checking of every managed site and persists historical
 * checks. Designed to be invoked by a cron (every 5 minutes) via the
 * `app:health:check-all` console command.
 *
 * Single responsibility: turn a list of ManagedSite into persisted
 * SiteHealthCheck rows + emit alerts on sustained failure. The HTTP wrapper
 * (SuperAdminClient) and the persistence layer (EM) stay decoupled so this
 * service can be swapped or tested in isolation.
 */
final readonly class HealthMonitor
{
    public const ALERT_THRESHOLD = 3;

    public function __construct(
        private ManagedSiteRepository $sites,
        private SiteHealthCheckRepository $healthChecks,
        private SuperAdminClient $client,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromEmail,
        private string $adminEmail,
    ) {
    }

    /**
     * Health-check every enabled site. Returns a per-site summary keyed by domain.
     *
     * @return array<string, array{status: string, latencyMs: ?int, alertSent: bool}>
     */
    public function checkAll(): array
    {
        $summary = [];
        foreach ($this->sites->findEnabled() as $site) {
            $summary[$site->getDomain()] = $this->checkOne($site);
        }
        return $summary;
    }

    /**
     * @return array{status: string, latencyMs: ?int, alertSent: bool}
     */
    public function checkOne(ManagedSite $site): array
    {
        return $this->recordResponse($site, $this->client->health($site));
    }

    /**
     * Persists a health response. Public so callers that already issued the HTTP
     * call (e.g. the admin live-check endpoint) can avoid a redundant request.
     *
     * @return array{status: string, latencyMs: ?int, alertSent: bool}
     */
    public function recordResponse(ManagedSite $site, HealthResponse $response): array
    {
        $check = new SiteHealthCheck($site, $response->status);
        $check->setLatencyMs($response->latencyMs);
        $check->setAppVersion($response->appVersion);
        $check->setError($response->errorReason);
        $this->em->persist($check);

        if ($response->status === 'ok' && $response->appVersion !== null) {
            $site->recordHealthOk($response->appVersion);
        } else {
            $site->recordHealthFailure($response->status, (string) $response->errorReason);
        }

        $this->em->flush();

        $alertSent = false;
        if ($response->status !== 'ok') {
            $consecutive = $this->healthChecks->consecutiveFailuresFor($site);
            if ($consecutive === self::ALERT_THRESHOLD) {
                $alertSent = $this->sendFailureAlert($site, $response, $consecutive);
            }
        }

        return [
            'status' => $response->status,
            'latencyMs' => $response->latencyMs,
            'alertSent' => $alertSent,
        ];
    }

    private function sendFailureAlert(ManagedSite $site, HealthResponse $response, int $consecutive): bool
    {
        try {
            $email = (new Email())
                ->from(new Address($this->fromEmail, 'rivierematthieu.com'))
                ->to($this->adminEmail)
                ->subject(sprintf('[ALERTE] %s — %d checks consécutifs KO', $site->getLabel(), $consecutive))
                ->text(sprintf(
                    "Le site %s (%s) a échoué %d fois de suite au health-check.\nDernier statut : %s\nErreur : %s\n",
                    $site->getLabel(),
                    $site->getDomain(),
                    $consecutive,
                    $response->status,
                    $response->errorReason ?? '(aucune)',
                ));
            $this->mailer->send($email);
            return true;
        } catch (\Throwable $e) {
            $this->logger->error('HealthMonitor alert failed', [
                'site' => $site->getDomain(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
