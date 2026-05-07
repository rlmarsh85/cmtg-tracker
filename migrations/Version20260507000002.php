<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_uri column to commander';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commander ADD COLUMN image_uri VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commander DROP COLUMN image_uri');
    }
}
