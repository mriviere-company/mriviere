<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema — admin_users, packages, quotes, contact_messages, profile_content.
 */
final class Version20260509000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for mriviere.eu';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE admin_users (
                id INT AUTO_INCREMENT NOT NULL,
                email VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles JSON NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                last_login_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
                UNIQUE INDEX UNIQ_admin_email (email),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE packages (
                id INT AUTO_INCREMENT NOT NULL,
                slug VARCHAR(32) NOT NULL,
                name VARCHAR(64) NOT NULL,
                one_shot_price_cents INT NOT NULL,
                monthly_price_cents INT NOT NULL,
                max_pages INT NOT NULL,
                features JSON NOT NULL,
                stripe_monthly_price_id VARCHAR(128) DEFAULT NULL,
                is_active TINYINT(1) NOT NULL,
                highlighted TINYINT(1) NOT NULL,
                sort_order INT NOT NULL,
                UNIQUE INDEX UNIQ_packages_slug (slug),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE quotes (
                id CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
                package_id INT NOT NULL,
                client_name VARCHAR(128) NOT NULL,
                client_company VARCHAR(128) DEFAULT NULL,
                client_email VARCHAR(180) NOT NULL,
                client_phone VARCHAR(32) NOT NULL,
                client_address VARCHAR(255) DEFAULT NULL,
                project_description LONGTEXT NOT NULL,
                options_json JSON NOT NULL,
                total_one_shot_cents INT NOT NULL,
                total_monthly_cents INT NOT NULL,
                status VARCHAR(32) NOT NULL,
                stripe_customer_id VARCHAR(128) DEFAULT NULL,
                stripe_checkout_session_id VARCHAR(128) DEFAULT NULL,
                stripe_subscription_id VARCHAR(128) DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                ip VARCHAR(45) DEFAULT NULL,
                INDEX IDX_quotes_package (package_id),
                INDEX IDX_quotes_status (status),
                INDEX IDX_quotes_checkout (stripe_checkout_session_id),
                INDEX IDX_quotes_subscription (stripe_subscription_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE contact_messages (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(128) NOT NULL,
                email VARCHAR(180) NOT NULL,
                message LONGTEXT NOT NULL,
                status VARCHAR(16) NOT NULL,
                created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                ip VARCHAR(45) DEFAULT NULL,
                INDEX IDX_contact_status (status),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE profile_content (
                id INT NOT NULL,
                bio LONGTEXT NOT NULL,
                stack JSON NOT NULL,
                links JSON NOT NULL,
                updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql('ALTER TABLE quotes ADD CONSTRAINT FK_quotes_package FOREIGN KEY (package_id) REFERENCES packages (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes DROP FOREIGN KEY FK_quotes_package');
        $this->addSql('DROP TABLE quotes');
        $this->addSql('DROP TABLE packages');
        $this->addSql('DROP TABLE contact_messages');
        $this->addSql('DROP TABLE profile_content');
        $this->addSql('DROP TABLE admin_users');
    }
}
