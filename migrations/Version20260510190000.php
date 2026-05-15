<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'profile_content.photo_url — optional URL/path to profile photo, falls back to SVG monogram.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profile_content ADD photo_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE profile_content DROP photo_url');
    }
}
