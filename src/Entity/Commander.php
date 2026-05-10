<?php

namespace App\Entity;

use App\Repository\CommanderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CommanderRepository::class)]
class Commander
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, unique: true)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: ColorIdentity::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ColorIdentity $colorIdentity = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $partnerType = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $partnerWith = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $imageUri = null;

    /** @var Collection<int, Deck> */
    #[ORM\OneToMany(targetEntity: Deck::class, mappedBy: 'commander')]
    private Collection $decksAsCommander;

    /** @var Collection<int, Deck> */
    #[ORM\OneToMany(targetEntity: Deck::class, mappedBy: 'partner')]
    private Collection $decksAsPartner;

    public function __construct()
    {
        $this->decksAsCommander = new ArrayCollection();
        $this->decksAsPartner   = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getColorIdentity(): ?ColorIdentity { return $this->colorIdentity; }
    public function setColorIdentity(?ColorIdentity $colorIdentity): static { $this->colorIdentity = $colorIdentity; return $this; }

    public function getPartnerType(): ?string { return $this->partnerType; }
    public function setPartnerType(?string $partnerType): static { $this->partnerType = $partnerType; return $this; }

    public function getPartnerWith(): ?string { return $this->partnerWith; }
    public function setPartnerWith(?string $partnerWith): static { $this->partnerWith = $partnerWith; return $this; }

    public function getImageUri(): ?string { return $this->imageUri; }
    public function setImageUri(?string $imageUri): static { $this->imageUri = $imageUri; return $this; }

    /** @return Collection<int, Deck> */
    public function getDecksAsCommander(): Collection { return $this->decksAsCommander; }

    /** @return Collection<int, Deck> */
    public function getDecksAsPartner(): Collection { return $this->decksAsPartner; }

    public function __toString(): string { return $this->name; }
}
