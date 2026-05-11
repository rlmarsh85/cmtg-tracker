<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Deck;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Player;
use PHPUnit\Framework\TestCase;

class GamePlayerTest extends TestCase
{
    private GamePlayer $gamePlayer;

    protected function setUp(): void
    {
        $this->gamePlayer = new GamePlayer();
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $this->assertNull($this->gamePlayer->getId());
    }

    public function testSetAndGetGame(): void
    {
        $game = $this->createStub(Game::class);
        $this->gamePlayer->setGame($game);
        $this->assertSame($game, $this->gamePlayer->getGame());
    }

    public function testSetAndGetPlayer(): void
    {
        $player = $this->createStub(Player::class);
        $this->gamePlayer->setPlayer($player);
        $this->assertSame($player, $this->gamePlayer->getPlayer());
    }

    public function testSetAndGetDeck(): void
    {
        $deck = $this->createStub(Deck::class);
        $this->gamePlayer->setDeck($deck);
        $this->assertSame($deck, $this->gamePlayer->getDeck());
    }

    public function testSetDeckToNull(): void
    {
        $this->gamePlayer->setDeck(null);
        $this->assertNull($this->gamePlayer->getDeck());
    }

    public function testDefaultWinnerIsFalse(): void
    {
        $this->assertFalse($this->gamePlayer->isWinner());
    }

    public function testSetWinnerToTrue(): void
    {
        $this->gamePlayer->setWinner(true);
        $this->assertTrue($this->gamePlayer->isWinner());
    }

    public function testSetWinnerToFalse(): void
    {
        $this->gamePlayer->setWinner(true);
        $this->gamePlayer->setWinner(false);
        $this->assertFalse($this->gamePlayer->isWinner());
    }

    public function testSetWinnerReturnsSelf(): void
    {
        $result = $this->gamePlayer->setWinner(true);
        $this->assertSame($this->gamePlayer, $result);
    }
}
