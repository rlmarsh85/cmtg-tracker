<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260511000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop placement column from game_player';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_player DROP COLUMN placement');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_player ADD COLUMN placement INTEGER DEFAULT NULL');
    }
}
