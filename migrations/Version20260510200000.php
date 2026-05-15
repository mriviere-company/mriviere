<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sprint 2 — daily stats cache per managed site (filled by app:stats:sync-yesterday).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE site_daily_stats (
                id INT AUTO_INCREMENT NOT NULL,
                site_id INT NOT NULL,
                day DATE NOT NULL,
                page_views INT NOT NULL DEFAULT 0,
                unique_visitors INT NOT NULL DEFAULT 0,
                top_paths JSON NOT NULL,
                synced_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE INDEX UNIQ_site_daily_stats (site_id, day),
                INDEX IDX_daily_stats_day (day),
                CONSTRAINT FK_daily_stats_site FOREIGN KEY (site_id) REFERENCES managed_sites (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_daily_stats');
    }
}
