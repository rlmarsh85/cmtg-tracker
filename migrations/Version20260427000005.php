<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add partner column to deck';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deck ADD COLUMN partner VARCHAR(150) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deck DROP COLUMN partner');
    }
}
