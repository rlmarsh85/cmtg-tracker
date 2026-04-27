<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Colorless color identity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Colorless')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM color_identity WHERE name = 'Colorless'");
    }
}
