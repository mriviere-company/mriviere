<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SiteDailyStatsRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Cached daily aggregate of stats fetched from a managed clone via
 * `/api/super-admin/stats/summary`. One row per site per day, idempotent on
 * re-sync (upsert on the unique (site, day) constraint).
 */
#[ORM\Entity(repositoryClass: SiteDailyStatsRepository::class)]
#[ORM\Table(name: 'site_daily_stats')]
#[ORM\UniqueConstraint(name: 'UNIQ_site_daily_stats', columns: ['site_id', 'day'])]
#[ORM\Index(columns: ['day'], name: 'IDX_daily_stats_day')]
class SiteDailyStats
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ManagedSite::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ManagedSite $site;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $day;

    #[ORM\Column(type: 'integer', name: 'page_views')]
    private int $pageViews = 0;

    #[ORM\Column(type: 'integer', name: 'unique_visitors')]
    private int $uniqueVisitors = 0;

    /** @var array<string, int> path → count */
    #[ORM\Column(type: 'json', name: 'top_paths')]
    private array $topPaths = [];

    #[ORM\Column(type: 'datetime_immutable', name: 'synced_at')]
    private \DateTimeImmutable $syncedAt;

    public function __construct(ManagedSite $site, \DateTimeImmutable $day)
    {
        $this->site = $site;
        $this->day = $day;
        $this->syncedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getSite(): ManagedSite { return $this->site; }
    public function getDay(): \DateTimeImmutable { return $this->day; }
    public function getPageViews(): int { return $this->pageViews; }
    public function setPageViews(int $v): self { $this->pageViews = $v; return $this; }
    public function getUniqueVisitors(): int { return $this->uniqueVisitors; }
    public function setUniqueVisitors(int $v): self { $this->uniqueVisitors = $v; return $this; }
    /** @return array<string, int> */
    public function getTopPaths(): array { return $this->topPaths; }
    /** @param array<string, int> $v */
    public function setTopPaths(array $v): self { $this->topPaths = $v; return $this; }
    public function getSyncedAt(): \DateTimeImmutable { return $this->syncedAt; }
    public function touch(): self { $this->syncedAt = new \DateTimeImmutable(); return $this; }
}
