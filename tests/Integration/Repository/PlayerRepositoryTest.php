<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PlayerRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private PlayerRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        $this->repo = self::getContainer()->get(PlayerRepository::class);
    }

    private function makePlayer(string $username, string $email): Player
    {
        $player = new Player();
        $player->setUsername($username);
        $player->setEmail($email);
        $this->em->persist($player);
        return $player;
    }

    private function makeGame(): Game
    {
        $game = new Game();
        $this->em->persist($game);
        return $game;
    }

    private function makeGamePlayer(Game $game, Player $player, bool $winner = false): GamePlayer
    {
        $gp = new GamePlayer();
        $gp->setGame($game);
        $gp->setPlayer($player);
        $gp->setWinner($winner);
        $this->em->persist($gp);
        return $gp;
    }

    public function testFindWithStatsReturnsEmptyArrayWhenNoPlayers(): void
    {
        $this->assertSame([], $this->repo->findWithStats());
    }

    public function testFindWithStatsReturnsRowsWithAggregates(): void
    {
        $player = $this->makePlayer('alice', 'alice@example.com');
        $game1  = $this->makeGame();
        $game2  = $this->makeGame();
        $this->makeGamePlayer($game1, $player, true);
        $this->makeGamePlayer($game2, $player, false);
        $this->em->flush();

        $results = $this->repo->findWithStats();
        $this->assertCount(1, $results);
        $row = $results[0];
        $this->assertSame($player, $row[0]);
        $this->assertEquals('2', $row['totalGames']);
        $this->assertEquals('1', $row['wins']);
    }

    public function testFindWithStatsIncludesPlayersWithNoGames(): void
    {
        $this->makePlayer('bob', 'bob@example.com');
        $this->em->flush();

        $results = $this->repo->findWithStats();
        $this->assertCount(1, $results);
        $row = $results[0];
        $this->assertEquals('0', (string) $row['totalGames']);
    }

    public function testFindWithStatsResultStructure(): void
    {
        $player = $this->makePlayer('charlie', 'charlie@example.com');
        $game   = $this->makeGame();
        $this->makeGamePlayer($game, $player);
        $this->em->flush();

        $results = $this->repo->findWithStats();
        $row = $results[0];
        $this->assertInstanceOf(Player::class, $row[0]);
        $this->assertArrayHasKey('totalGames', $row);
        $this->assertArrayHasKey('wins', $row);
    }

    public function testFindWithStatsOrderedByUsernameAscending(): void
    {
        $this->makePlayer('Zara', 'zara@example.com');
        $this->makePlayer('Alice', 'alice@example.com');
        $this->em->flush();

        $results = $this->repo->findWithStats();
        $this->assertCount(2, $results);
        $this->assertSame('Alice', $results[0][0]->getUsername());
        $this->assertSame('Zara', $results[1][0]->getUsername());
    }

    public function testFindWithStatsCountsWinsCorrectly(): void
    {
        $player = $this->makePlayer('dave', 'dave@example.com');
        $game1  = $this->makeGame();
        $game2  = $this->makeGame();
        $game3  = $this->makeGame();
        $this->makeGamePlayer($game1, $player, true);
        $this->makeGamePlayer($game2, $player, true);
        $this->makeGamePlayer($game3, $player, false);
        $this->em->flush();

        $results = $this->repo->findWithStats();
        $row = $results[0];
        $this->assertEquals('3', $row['totalGames']);
        $this->assertEquals('2', $row['wins']);
    }

    public function testFindWithStatsMultiplePlayers(): void
    {
        $alice = $this->makePlayer('alice', 'alice@example.com');
        $bob   = $this->makePlayer('bob', 'bob@example.com');
        $game  = $this->makeGame();
        $this->makeGamePlayer($game, $alice, true);
        $this->makeGamePlayer($game, $bob, false);
        $this->em->flush();

        $results = $this->repo->findWithStats();
        $this->assertCount(2, $results);

        $aliceRow = $results[0];
        $this->assertSame($alice, $aliceRow[0]);
        $this->assertEquals('1', $aliceRow['totalGames']);
        $this->assertEquals('1', $aliceRow['wins']);

        $bobRow = $results[1];
        $this->assertSame($bob, $bobRow[0]);
        $this->assertEquals('1', $bobRow['totalGames']);
        $this->assertEquals('0', (string) $bobRow['wins']);
    }
}
