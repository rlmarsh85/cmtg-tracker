<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Game;
use App\Entity\GamePlayer;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class GameTest extends TestCase
{
    private Game $game;

    protected function setUp(): void
    {
        $this->game = new Game();
    }

    public function testConstructorSetsPlayedAtToNow(): void
    {
        $now = new \DateTimeImmutable();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->game->getPlayedAt());
        $this->assertLessThanOrEqual(2, abs($now->getTimestamp() - $this->game->getPlayedAt()->getTimestamp()));
    }

    public function testConstructorSetsCreatedAtToNow(): void
    {
        $now = new \DateTimeImmutable();
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->game->getCreatedAt());
        $this->assertLessThanOrEqual(2, abs($now->getTimestamp() - $this->game->getCreatedAt()->getTimestamp()));
    }

    public function testConstructorInitializesParticipantsCollection(): void
    {
        $this->assertInstanceOf(Collection::class, $this->game->getParticipants());
        $this->assertCount(0, $this->game->getParticipants());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->game->getId());
    }

    public function testSetAndGetPlayedAt(): void
    {
        $date = new \DateTimeImmutable('2024-01-15');
        $this->game->setPlayedAt($date);
        $this->assertSame($date, $this->game->getPlayedAt());
    }

    public function testSetPlayedAtReturnsSelf(): void
    {
        $result = $this->game->setPlayedAt(new \DateTimeImmutable());
        $this->assertSame($this->game, $result);
    }

    public function testDefaultFormatIsCommander(): void
    {
        $this->assertSame('Commander', $this->game->getFormat());
    }

    public function testSetAndGetFormat(): void
    {
        $this->game->setFormat('Legacy');
        $this->assertSame('Legacy', $this->game->getFormat());
    }

    public function testSetAndGetNotes(): void
    {
        $this->game->setNotes('Great game!');
        $this->assertSame('Great game!', $this->game->getNotes());
    }

    public function testSetNotesToNull(): void
    {
        $this->game->setNotes(null);
        $this->assertNull($this->game->getNotes());
    }

    public function testAddParticipantIncreasesCollection(): void
    {
        $gp = new GamePlayer();
        $this->game->addParticipant($gp);
        $this->assertCount(1, $this->game->getParticipants());
    }

    public function testAddParticipantSetsGameOnGamePlayer(): void
    {
        $gp = new GamePlayer();
        $this->game->addParticipant($gp);
        $this->assertSame($this->game, $gp->getGame());
    }

    public function testAddSameParticipantTwiceCountsOnce(): void
    {
        $gp = new GamePlayer();
        $this->game->addParticipant($gp);
        $this->game->addParticipant($gp);
        $this->assertCount(1, $this->game->getParticipants());
    }

    public function testRemoveParticipantDecreasesCollection(): void
    {
        $gp = new GamePlayer();
        $this->game->addParticipant($gp);
        $this->game->removeParticipant($gp);
        $this->assertCount(0, $this->game->getParticipants());
    }

    public function testRemoveParticipantNullsGameReference(): void
    {
        $gp = new GamePlayer();
        $this->game->addParticipant($gp);
        $this->game->removeParticipant($gp);
        $this->assertNull($gp->getGame());
    }

    public function testGetWinnerReturnsNullWithNoParticipants(): void
    {
        $this->assertNull($this->game->getWinner());
    }

    public function testGetWinnerReturnsNullWhenNoWinnerSet(): void
    {
        $gp1 = new GamePlayer();
        $gp1->setWinner(false);
        $gp2 = new GamePlayer();
        $gp2->setWinner(false);
        $this->game->addParticipant($gp1);
        $this->game->addParticipant($gp2);
        $this->assertNull($this->game->getWinner());
    }

    public function testGetWinnerReturnsWinningParticipant(): void
    {
        $loser = new GamePlayer();
        $loser->setWinner(false);
        $winner = new GamePlayer();
        $winner->setWinner(true);
        $this->game->addParticipant($loser);
        $this->game->addParticipant($winner);
        $this->assertSame($winner, $this->game->getWinner());
    }

    public function testGetWinnerReturnsFirstWinnerWhenMultipleExist(): void
    {
        $winner1 = new GamePlayer();
        $winner1->setWinner(true);
        $winner2 = new GamePlayer();
        $winner2->setWinner(true);
        $this->game->addParticipant($winner1);
        $this->game->addParticipant($winner2);
        $this->assertSame($winner1, $this->game->getWinner());
    }
}
