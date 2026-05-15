<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sprint 2 central dashboard — historical health checks per managed site';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE site_health_checks (
                id INT AUTO_INCREMENT NOT NULL,
                site_id INT NOT NULL,
                status VARCHAR(32) NOT NULL,
                latency_ms INT DEFAULT NULL,
                app_version VARCHAR(32) DEFAULT NULL,
                error LONGTEXT DEFAULT NULL,
                checked_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX IDX_health_site_time (site_id, checked_at),
                CONSTRAINT FK_health_site FOREIGN KEY (site_id) REFERENCES managed_sites (id) ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE site_health_checks');
    }
}
