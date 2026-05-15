<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add managed_sites table — registry of client clones consumed by the central dashboard.
 */
final class Version20260509000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add managed_sites table for the central super-admin dashboard';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE managed_sites (
                id INT AUTO_INCREMENT NOT NULL,
                domain VARCHAR(191) NOT NULL,
                label VARCHAR(128) NOT NULL,
                public_key_fingerprint VARCHAR(128) NOT NULL,
                enabled TINYINT(1) NOT NULL,
                added_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                last_seen_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                last_health_status VARCHAR(32) DEFAULT NULL,
                last_health_error LONGTEXT DEFAULT NULL,
                last_app_version VARCHAR(32) DEFAULT NULL,
                UNIQUE INDEX UNIQ_managed_sites_domain (domain),
                INDEX IDX_managed_sites_enabled (enabled),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE managed_sites');
    }
}
