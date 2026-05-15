<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260510170035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add locale column to quotes for bilingual email templates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE quotes ADD locale VARCHAR(5) DEFAULT 'fr' NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE quotes DROP locale');
    }
}
