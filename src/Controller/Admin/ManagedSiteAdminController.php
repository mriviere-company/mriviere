<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Dto\ManagedSitePayload;
use App\Entity\ManagedSite;
use App\Entity\SiteDailyStats;
use App\Entity\SiteHealthCheck;
use App\Repository\ManagedSiteRepository;
use App\Repository\SiteDailyStatsRepository;
use App\Repository\SiteHealthCheckRepository;
use App\Service\RequestPayloadDeserializer;
use App\Service\SuperAdmin\HealthMonitor;
use App\Service\SuperAdmin\JwtSigner;
use App\Service\SuperAdmin\SuperAdminClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/sites', name: 'api_admin_sites_')]
#[IsGranted('ROLE_ADMIN')]
final class ManagedSiteAdminController extends AbstractController
{
    public function __construct(
        private readonly ManagedSiteRepository $sites,
        private readonly EntityManagerInterface $em,
        private readonly RequestPayloadDeserializer $deserializer,
        private readonly SuperAdminClient $superAdminClient,
        private readonly JwtSigner $jwtSigner,
        private readonly HealthMonitor $healthMonitor,
        private readonly SiteHealthCheckRepository $healthChecks,
        private readonly SiteDailyStatsRepository $dailyStats,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json([
            'centralFingerprint' => $this->jwtSigner->getPublicKeyFingerprint(),
            'sites' => array_map([$this, 'serialize'], $this->sites->findAllOrdered()),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        [$dto, $errors] = $this->deserializer->deserializeAndValidate($request, ManagedSitePayload::class);
        if ($errors !== []) {
            return $this->json(['error' => 'Champs invalides.', 'fields' => $errors], 400);
        }
        \assert($dto instanceof ManagedSitePayload);

        if ($this->sites->findByDomain($dto->domain) !== null) {
            return $this->json(['error' => 'Ce domaine est déjà enregistré.', 'fields' => ['domain' => 'Domaine déjà présent.']], 409);
        }

        $site = new ManagedSite($dto->domain, $dto->label, strtolower($dto->publicKeyFingerprint));
        $site->setEnabled($dto->enabled);
        $this->em->persist($site);
        $this->em->flush();

        return $this->json($this->serialize($site), 201);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }
        return $this->json($this->serialize($site));
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }

        [$dto, $errors] = $this->deserializer->deserializeAndValidate($request, ManagedSitePayload::class);
        if ($errors !== []) {
            return $this->json(['error' => 'Champs invalides.', 'fields' => $errors], 400);
        }
        \assert($dto instanceof ManagedSitePayload);

        $site->setDomain($dto->domain)
            ->setLabel($dto->label)
            ->setPublicKeyFingerprint(strtolower($dto->publicKeyFingerprint))
            ->setEnabled($dto->enabled);
        $this->em->flush();

        return $this->json($this->serialize($site));
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }
        $this->em->remove($site);
        $this->em->flush();
        return $this->json(['ok' => true]);
    }

    /** Live health check — pings the clone immediately, persists a history row and may trigger an alert. */
    #[Route('/{id}/check-health', name: 'check_health', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function checkHealth(int $id): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }
        if (!$site->isEnabled()) {
            return $this->json(['error' => 'Ce site est désactivé — réactive-le pour vérifier sa santé.'], 409);
        }

        // Single HTTP call — pass the response to HealthMonitor for persistence + alerting.
        $live = $this->superAdminClient->health($site);
        $result = $this->healthMonitor->recordResponse($site, $live);

        return $this->json([
            'site' => $this->serialize($site),
            'check' => [
                'status' => $live->status,
                'httpStatus' => $live->httpStatus,
                'latencyMs' => $live->latencyMs,
                'appVersion' => $live->appVersion,
                'phpVersion' => $live->phpVersion,
                'databaseOk' => $live->databaseOk,
                'freeDiskBytes' => $live->freeDiskBytes,
                'observedAt' => $live->observedAt?->format(\DateTimeInterface::ATOM),
                'errorReason' => $live->errorReason,
                'alertSent' => $result['alertSent'],
            ],
        ]);
    }

    #[Route('/{id}/stats', name: 'stats', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function stats(int $id): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }

        $rows = $this->dailyStats->timeseriesForSite($site, 30);

        $aggregatedTopPaths = [];
        foreach ($rows as $row) {
            foreach ($row->getTopPaths() as $path => $count) {
                $aggregatedTopPaths[$path] = ($aggregatedTopPaths[$path] ?? 0) + $count;
            }
        }
        arsort($aggregatedTopPaths);
        $aggregatedTopPaths = array_slice($aggregatedTopPaths, 0, 10, true);

        return $this->json([
            'site' => $this->serialize($site),
            'totals' => [
                'pageViews' => array_sum(array_map(fn(SiteDailyStats $r) => $r->getPageViews(), $rows)),
                'uniqueVisitors' => array_sum(array_map(fn(SiteDailyStats $r) => $r->getUniqueVisitors(), $rows)),
            ],
            'timeseries' => array_map(fn(SiteDailyStats $r) => [
                'day' => $r->getDay()->format('Y-m-d'),
                'pageViews' => $r->getPageViews(),
                'uniqueVisitors' => $r->getUniqueVisitors(),
            ], $rows),
            'topPaths' => $aggregatedTopPaths,
        ]);
    }

    #[Route('/{id}/admins', name: 'remote_admins_list', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function listRemoteAdmins(int $id): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }
        $admins = $this->superAdminClient->listAdmins($site);
        return $this->json(['admins' => array_map(fn($a) => [
            'id' => $a->id,
            'email' => $a->email,
            'enabled' => $a->enabled,
            'lastLoginAt' => $a->lastLoginAt?->format(\DateTimeInterface::ATOM),
            'createdAt' => $a->createdAt->format(\DateTimeInterface::ATOM),
        ], $admins)]);
    }

    #[Route('/{id}/admins', name: 'remote_admins_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createRemoteAdmin(int $id, Request $request): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }
        $payload = json_decode($request->getContent(), true);
        $email = is_array($payload) && isset($payload['email']) ? trim((string) $payload['email']) : '';
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->json(['error' => 'Email invalide.'], 400);
        }
        $result = $this->superAdminClient->createRemoteAdmin($site, $email);
        if (!$result['ok']) {
            return $this->json(['error' => $result['error'] ?? 'Erreur inconnue.'], 502);
        }
        $admin = $result['admin'] ?? null;
        return $this->json([
            'admin' => $admin === null ? null : [
                'id' => $admin->id,
                'email' => $admin->email,
                'enabled' => $admin->enabled,
                'lastLoginAt' => $admin->lastLoginAt?->format(\DateTimeInterface::ATOM),
                'createdAt' => $admin->createdAt->format(\DateTimeInterface::ATOM),
            ],
            // Surface the temporary password ONCE — caller must show + never re-fetch.
            'temporaryPassword' => $result['temporaryPassword'] ?? '',
        ], 201);
    }

    #[Route('/{id}/admins/{adminId}/{action}', name: 'remote_admins_toggle', methods: ['POST'], requirements: ['id' => '\d+', 'adminId' => '\d+', 'action' => 'enable|disable'])]
    public function toggleRemoteAdmin(int $id, int $adminId, string $action): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }
        $result = $this->superAdminClient->setRemoteAdminEnabled($site, $adminId, $action === 'enable');
        if (!$result['ok']) {
            return $this->json(['error' => $result['error'] ?? 'Erreur inconnue.'], 502);
        }
        return $this->json(['ok' => true]);
    }

    #[Route('/{id}/health-history', name: 'health_history', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function healthHistory(int $id): JsonResponse
    {
        $site = $this->sites->find($id);
        if ($site === null) {
            return $this->json(['error' => 'Site introuvable.'], 404);
        }

        $checks = $this->healthChecks->recentForSite($site, 50);

        return $this->json([
            'site' => $this->serialize($site),
            'consecutiveFailures' => $this->healthChecks->consecutiveFailuresFor($site),
            'history' => array_map(fn(SiteHealthCheck $h) => [
                'id' => $h->getId(),
                'status' => $h->getStatus(),
                'latencyMs' => $h->getLatencyMs(),
                'appVersion' => $h->getAppVersion(),
                'error' => $h->getError(),
                'checkedAt' => $h->getCheckedAt()->format(\DateTimeInterface::ATOM),
            ], $checks),
        ]);
    }

    /** @return array<string, mixed> */
    private function serialize(ManagedSite $s): array
    {
        return [
            'id' => $s->getId(),
            'domain' => $s->getDomain(),
            'label' => $s->getLabel(),
            'publicKeyFingerprint' => $s->getPublicKeyFingerprint(),
            'enabled' => $s->isEnabled(),
            'addedAt' => $s->getAddedAt()->format(\DateTimeInterface::ATOM),
            'lastSeenAt' => $s->getLastSeenAt()?->format(\DateTimeInterface::ATOM),
            'lastHealthStatus' => $s->getLastHealthStatus(),
            'lastHealthError' => $s->getLastHealthError(),
            'lastAppVersion' => $s->getLastAppVersion(),
            'baseUrl' => $s->getBaseUrl(),
        ];
    }
}
