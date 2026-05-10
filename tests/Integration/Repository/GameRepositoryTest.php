<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private GameRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        $this->repo = self::getContainer()->get(GameRepository::class);
    }

    private function makeGame(string $date): Game
    {
        $game = new Game();
        $game->setPlayedAt(new \DateTimeImmutable($date));
        $this->em->persist($game);
        return $game;
    }

    public function testFindRecentReturnsEmptyArrayWhenNoGames(): void
    {
        $this->assertSame([], $this->repo->findRecent(5));
    }

    public function testFindRecentReturnsGamesOrderedByPlayedAtDescending(): void
    {
        $oldest  = $this->makeGame('2024-01-01');
        $middle  = $this->makeGame('2024-06-01');
        $newest  = $this->makeGame('2024-12-01');
        $this->em->flush();

        $results = $this->repo->findRecent(10);
        $this->assertCount(3, $results);
        $this->assertSame($newest->getId(), $results[0]->getId());
        $this->assertSame($middle->getId(), $results[1]->getId());
        $this->assertSame($oldest->getId(), $results[2]->getId());
    }

    public function testFindRecentRespectsLimitParameter(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->makeGame("2024-0{$i}-01");
        }
        $this->em->flush();

        $results = $this->repo->findRecent(3);
        $this->assertCount(3, $results);
    }

    public function testFindRecentDefaultLimitIsTen(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $date = sprintf('2024-%02d-01', $i);
            $this->makeGame($date);
        }
        $this->em->flush();

        $results = $this->repo->findRecent();
        $this->assertCount(10, $results);
    }

    public function testFindRecentReturnsGameInstances(): void
    {
        $this->makeGame('2024-03-15');
        $this->em->flush();

        $results = $this->repo->findRecent(1);
        $this->assertInstanceOf(Game::class, $results[0]);
    }

    public function testFindRecentWithLimitZeroReturnsEmpty(): void
    {
        $this->makeGame('2024-01-01');
        $this->em->flush();

        $results = $this->repo->findRecent(0);
        $this->assertCount(0, $results);
    }
}
