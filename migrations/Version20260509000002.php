<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds the callback_requests table for the « Réserver un appel » CTA on the homepage.
 */
final class Version20260509000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add callback_requests table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE callback_requests (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(128) NOT NULL,
                email VARCHAR(180) NOT NULL,
                phone VARCHAR(32) NOT NULL,
                preferred_slot DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                message LONGTEXT DEFAULT NULL,
                status VARCHAR(16) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                ip VARCHAR(45) DEFAULT NULL,
                INDEX IDX_callbacks_status (status),
                INDEX IDX_callbacks_slot (preferred_slot),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE callback_requests');
    }
}
