<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260427000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed colors and color identities';
    }

    public function up(Schema $schema): void
    {
        // Colors: Blue=1, White=2, Black=3, Red=4, Green=5
        $this->addSql("INSERT INTO color (name) VALUES ('Blue')");
        $this->addSql("INSERT INTO color (name) VALUES ('White')");
        $this->addSql("INSERT INTO color (name) VALUES ('Black')");
        $this->addSql("INSERT INTO color (name) VALUES ('Red')");
        $this->addSql("INSERT INTO color (name) VALUES ('Green')");

        // Two-color guilds
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Azorius')");   // 1  Blue/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Dimir')");     // 2  Black/Blue
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Rakdos')");    // 3  Black/Red
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Gruul')");     // 4  Green/Red
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Selesnya')");  // 5  Green/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Orzhov')");    // 6  Black/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Izzet')");     // 7  Blue/Red
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Golgari')");   // 8  Black/Green
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Boros')");     // 9  Red/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Simic')");     // 10 Blue/Green

        // Three-color shards/wedges
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Jund')");      // 11 Black/Green/Red
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Bant')");      // 12 Blue/Green/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Grixis')");    // 13 Black/Blue/Red
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Naya')");      // 14 Green/Red/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Esper')");     // 15 Black/Blue/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Jeskai')");    // 16 Blue/Red/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Mardu')");     // 17 Black/Red/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Sultai')");    // 18 Black/Blue/Green
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Ketria')");    // 19 Blue/Green/Red
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Abzan')");     // 20 Black/Green/White

        // Four-color
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Non-white')"); // 21 Black/Blue/Green/Red
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Non-blue')");  // 22 Black/Green/Red/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Non-black')"); // 23 Blue/Green/Red/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Non-red')");   // 24 Black/Blue/Green/White
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Non-green')"); // 25 Black/Blue/Red/White

        // Five-color
        $this->addSql("INSERT INTO color_identity (name) VALUES ('Rainbow')");   // 26 All five

        // Join table: (color_identity_id, color_id)  Blue=1 White=2 Black=3 Red=4 Green=5
        // Azorius (1): Blue, White
        $this->addSql('INSERT INTO color_identity_color VALUES (1, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (1, 2)');
        // Dimir (2): Black, Blue
        $this->addSql('INSERT INTO color_identity_color VALUES (2, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (2, 1)');
        // Rakdos (3): Black, Red
        $this->addSql('INSERT INTO color_identity_color VALUES (3, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (3, 4)');
        // Gruul (4): Green, Red
        $this->addSql('INSERT INTO color_identity_color VALUES (4, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (4, 4)');
        // Selesnya (5): Green, White
        $this->addSql('INSERT INTO color_identity_color VALUES (5, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (5, 2)');
        // Orzhov (6): Black, White
        $this->addSql('INSERT INTO color_identity_color VALUES (6, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (6, 2)');
        // Izzet (7): Blue, Red
        $this->addSql('INSERT INTO color_identity_color VALUES (7, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (7, 4)');
        // Golgari (8): Black, Green
        $this->addSql('INSERT INTO color_identity_color VALUES (8, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (8, 5)');
        // Boros (9): Red, White
        $this->addSql('INSERT INTO color_identity_color VALUES (9, 4)');
        $this->addSql('INSERT INTO color_identity_color VALUES (9, 2)');
        // Simic (10): Blue, Green
        $this->addSql('INSERT INTO color_identity_color VALUES (10, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (10, 5)');
        // Jund (11): Black, Green, Red
        $this->addSql('INSERT INTO color_identity_color VALUES (11, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (11, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (11, 4)');
        // Bant (12): Blue, Green, White
        $this->addSql('INSERT INTO color_identity_color VALUES (12, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (12, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (12, 2)');
        // Grixis (13): Black, Blue, Red
        $this->addSql('INSERT INTO color_identity_color VALUES (13, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (13, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (13, 4)');
        // Naya (14): Green, Red, White
        $this->addSql('INSERT INTO color_identity_color VALUES (14, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (14, 4)');
        $this->addSql('INSERT INTO color_identity_color VALUES (14, 2)');
        // Esper (15): Black, Blue, White
        $this->addSql('INSERT INTO color_identity_color VALUES (15, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (15, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (15, 2)');
        // Jeskai (16): Blue, Red, White
        $this->addSql('INSERT INTO color_identity_color VALUES (16, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (16, 4)');
        $this->addSql('INSERT INTO color_identity_color VALUES (16, 2)');
        // Mardu (17): Black, Red, White
        $this->addSql('INSERT INTO color_identity_color VALUES (17, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (17, 4)');
        $this->addSql('INSERT INTO color_identity_color VALUES (17, 2)');
        // Sultai (18): Black, Blue, Green
        $this->addSql('INSERT INTO color_identity_color VALUES (18, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (18, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (18, 5)');
        // Ketria (19): Blue, Green, Red
        $this->addSql('INSERT INTO color_identity_color VALUES (19, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (19, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (19, 4)');
        // Abzan (20): Black, Green, White
        $this->addSql('INSERT INTO color_identity_color VALUES (20, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (20, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (20, 2)');
        // Non-white (21): Black, Blue, Green, Red
        $this->addSql('INSERT INTO color_identity_color VALUES (21, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (21, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (21, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (21, 4)');
        // Non-blue (22): Black, Green, Red, White
        $this->addSql('INSERT INTO color_identity_color VALUES (22, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (22, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (22, 4)');
        $this->addSql('INSERT INTO color_identity_color VALUES (22, 2)');
        // Non-black (23): Blue, Green, Red, White
        $this->addSql('INSERT INTO color_identity_color VALUES (23, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (23, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (23, 4)');
        $this->addSql('INSERT INTO color_identity_color VALUES (23, 2)');
        // Non-red (24): Black, Blue, Green, White
        $this->addSql('INSERT INTO color_identity_color VALUES (24, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (24, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (24, 5)');
        $this->addSql('INSERT INTO color_identity_color VALUES (24, 2)');
        // Non-green (25): Black, Blue, Red, White
        $this->addSql('INSERT INTO color_identity_color VALUES (25, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (25, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (25, 4)');
        $this->addSql('INSERT INTO color_identity_color VALUES (25, 2)');
        // Rainbow (26): all five
        $this->addSql('INSERT INTO color_identity_color VALUES (26, 1)');
        $this->addSql('INSERT INTO color_identity_color VALUES (26, 2)');
        $this->addSql('INSERT INTO color_identity_color VALUES (26, 3)');
        $this->addSql('INSERT INTO color_identity_color VALUES (26, 4)');
        $this->addSql('INSERT INTO color_identity_color VALUES (26, 5)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM color_identity_color');
        $this->addSql('DELETE FROM color_identity');
        $this->addSql('DELETE FROM color');
    }
}
