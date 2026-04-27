<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add color and color_identity tables; add color_identity_id to deck';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS color (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(20) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_COLOR_NAME ON color (name)');

        $this->addSql('CREATE TABLE IF NOT EXISTS color_identity (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(50) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS UNIQ_CI_NAME ON color_identity (name)');

        $this->addSql('CREATE TABLE IF NOT EXISTS color_identity_color (color_identity_id INTEGER NOT NULL, color_id INTEGER NOT NULL, PRIMARY KEY(color_identity_id, color_id))');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_CIC_IDENTITY ON color_identity_color (color_identity_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_CIC_COLOR ON color_identity_color (color_id)');

        if ($schema->hasTable('deck') && !$schema->getTable('deck')->hasColumn('color_identity_id')) {
            $this->addSql('ALTER TABLE deck ADD COLUMN color_identity_id INTEGER DEFAULT NULL REFERENCES color_identity(id)');
            $this->addSql('CREATE INDEX IF NOT EXISTS IDX_DECK_COLOR_IDENTITY ON deck (color_identity_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('deck') && $schema->getTable('deck')->hasColumn('color_identity_id')) {
            $this->addSql('ALTER TABLE deck DROP COLUMN color_identity_id');
        }
        $this->addSql('DROP TABLE IF EXISTS color_identity_color');
        $this->addSql('DROP TABLE IF EXISTS color_identity');
        $this->addSql('DROP TABLE IF EXISTS color');
    }
}
