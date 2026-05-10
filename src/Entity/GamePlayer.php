<?php

namespace App\Entity;

use App\Repository\GamePlayerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GamePlayerRepository::class)]
class GamePlayer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private Game $game;

    #[ORM\ManyToOne(inversedBy: 'gamePlayers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private Player $player;

    #[ORM\ManyToOne(inversedBy: 'gamePlayers')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Deck $deck = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 1, max: 20)]
    private ?int $placement = null;

    #[ORM\Column]
    private bool $winner = false;

    public function getId(): ?int { return $this->id; }

    public function getGame(): Game { return $this->game; }
    public function setGame(Game $game): static { $this->game = $game; return $this; }

    public function getPlayer(): Player { return $this->player; }
    public function setPlayer(Player $player): static { $this->player = $player; return $this; }

    public function getDeck(): ?Deck { return $this->deck; }
    public function setDeck(?Deck $deck): static { $this->deck = $deck; return $this; }

    public function getPlacement(): ?int { return $this->placement; }
    public function setPlacement(?int $placement): static { $this->placement = $placement; return $this; }

    public function isWinner(): bool { return $this->winner; }
    public function setWinner(bool $winner): static { $this->winner = $winner; return $this; }
}
