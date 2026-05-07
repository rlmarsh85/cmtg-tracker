<?php

namespace App\Entity;

use App\Repository\DeckRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DeckRepository::class)]
class Deck
{
    public const FORMATS = ['Commander', 'Standard', 'Modern', 'Pioneer', 'Legacy', 'Vintage', 'Draft', 'Sealed', 'Pauper', 'Other'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Commander::class, inversedBy: 'decksAsCommander')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Commander $commander = null;

    #[ORM\ManyToOne(targetEntity: Commander::class, inversedBy: 'decksAsPartner')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Commander $partner = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: Deck::FORMATS)]
    private string $format = 'Commander';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'decks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Player $player = null;

    #[ORM\ManyToOne(targetEntity: ColorIdentity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ColorIdentity $colorIdentity = null;

    #[ORM\OneToMany(targetEntity: GamePlayer::class, mappedBy: 'deck')]
    private Collection $gamePlayers;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->gamePlayers = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getCommander(): ?Commander { return $this->commander; }
    public function setCommander(?Commander $commander): static { $this->commander = $commander; return $this; }

    public function getPartner(): ?Commander { return $this->partner; }
    public function setPartner(?Commander $partner): static { $this->partner = $partner; return $this; }

    public function getFormat(): string { return $this->format; }
    public function setFormat(string $format): static { $this->format = $format; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getPlayer(): ?Player { return $this->player; }
    public function setPlayer(?Player $player): static { $this->player = $player; return $this; }

    public function getColorIdentity(): ?ColorIdentity { return $this->colorIdentity; }
    public function setColorIdentity(?ColorIdentity $colorIdentity): static { $this->colorIdentity = $colorIdentity; return $this; }

    public function getGamePlayers(): Collection { return $this->gamePlayers; }

    public function __toString(): string { return $this->name ?? ''; }
}
