<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mono-color identities (White, Blue, Black, Red, Green)';
    }

    public function up(Schema $schema): void
    {
        // Colors: Blue=1, White=2, Black=3, Red=4, Green=5
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Mono-White')");  // 28
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Mono-Blue')");   // 29
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Mono-Black')");  // 30
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Mono-Red')");    // 31
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Mono-Green')");  // 32

        $this->addSql("INSERT INTO color_identity_color SELECT id, 2 FROM color_identity WHERE name = 'Mono-White'");
        $this->addSql("INSERT INTO color_identity_color SELECT id, 1 FROM color_identity WHERE name = 'Mono-Blue'");
        $this->addSql("INSERT INTO color_identity_color SELECT id, 3 FROM color_identity WHERE name = 'Mono-Black'");
        $this->addSql("INSERT INTO color_identity_color SELECT id, 4 FROM color_identity WHERE name = 'Mono-Red'");
        $this->addSql("INSERT INTO color_identity_color SELECT id, 5 FROM color_identity WHERE name = 'Mono-Green'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM color_identity_color WHERE color_identity_id IN (SELECT id FROM color_identity WHERE name IN ('Mono-White','Mono-Blue','Mono-Black','Mono-Red','Mono-Green'))");
        $this->addSql("DELETE FROM color_identity WHERE name IN ('Mono-White','Mono-Blue','Mono-Black','Mono-Red','Mono-Green')");
    }
}
