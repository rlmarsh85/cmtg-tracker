<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260507000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add commander table; migrate deck.commander/partner strings to entity relations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE commander (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            color_identity_id INTEGER DEFAULT NULL,
            name VARCHAR(150) NOT NULL,
            partner_type VARCHAR(30) DEFAULT NULL,
            partner_with VARCHAR(150) DEFAULT NULL,
            CONSTRAINT UNIQ_commander_name UNIQUE (name),
            CONSTRAINT FK_commander_color_identity FOREIGN KEY (color_identity_id) REFERENCES color_identity (id)
        )');

        // Migrate distinct commander names from existing decks (color identity unknown yet)
        $this->addSql('INSERT OR IGNORE INTO commander (name)
            SELECT DISTINCT commander FROM deck WHERE commander IS NOT NULL AND commander != \'\'');
        $this->addSql('INSERT OR IGNORE INTO commander (name)
            SELECT DISTINCT partner FROM deck WHERE partner IS NOT NULL AND partner != \'\'');

        // Add FK columns to deck
        $this->addSql('ALTER TABLE deck ADD COLUMN commander_id INTEGER DEFAULT NULL REFERENCES commander(id)');
        $this->addSql('ALTER TABLE deck ADD COLUMN partner_id INTEGER DEFAULT NULL REFERENCES commander(id)');

        // Populate FK columns from existing string values
        $this->addSql('UPDATE deck SET commander_id = (SELECT id FROM commander WHERE name = deck.commander)
            WHERE deck.commander IS NOT NULL AND deck.commander != \'\'');
        $this->addSql('UPDATE deck SET partner_id = (SELECT id FROM commander WHERE name = deck.partner)
            WHERE deck.partner IS NOT NULL AND deck.partner != \'\'');

        // Drop old string columns
        $this->addSql('ALTER TABLE deck DROP COLUMN commander');
        $this->addSql('ALTER TABLE deck DROP COLUMN partner');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE deck ADD COLUMN commander VARCHAR(150) DEFAULT NULL');
        $this->addSql('ALTER TABLE deck ADD COLUMN partner VARCHAR(150) DEFAULT NULL');
        $this->addSql('UPDATE deck SET commander = (SELECT name FROM commander WHERE id = deck.commander_id)
            WHERE deck.commander_id IS NOT NULL');
        $this->addSql('UPDATE deck SET partner = (SELECT name FROM commander WHERE id = deck.partner_id)
            WHERE deck.partner_id IS NOT NULL');
        $this->addSql('ALTER TABLE deck DROP COLUMN commander_id');
        $this->addSql('ALTER TABLE deck DROP COLUMN partner_id');
        $this->addSql('DROP TABLE commander');
    }
}
