<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date_immutable')]
    #[Assert\NotNull]
    private \DateTimeImmutable $playedAt;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: \App\Entity\Deck::FORMATS)]
    private string $format = 'Commander';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive]
    private ?int $turnCount = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, GamePlayer> */
    #[ORM\OneToMany(targetEntity: GamePlayer::class, mappedBy: 'game', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participants;

    public function __construct()
    {
        $this->playedAt = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getPlayedAt(): \DateTimeImmutable { return $this->playedAt; }
    public function setPlayedAt(\DateTimeImmutable $playedAt): static { $this->playedAt = $playedAt; return $this; }

    public function getFormat(): string { return $this->format; }
    public function setFormat(string $format): static { $this->format = $format; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getTurnCount(): ?int { return $this->turnCount; }
    public function setTurnCount(?int $turnCount): static { $this->turnCount = $turnCount; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    /** @return Collection<int, GamePlayer> */
    public function getParticipants(): Collection { return $this->participants; }

    public function addParticipant(GamePlayer $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setGame($this);
        }
        return $this;
    }

    public function removeParticipant(GamePlayer $participant): static
    {
        $this->participants->removeElement($participant);
        return $this;
    }

    public function getWinner(): ?GamePlayer
    {
        foreach ($this->participants as $p) {
            if ($p->isWinner()) return $p;
        }
        return null;
    }
}
