<?php

namespace App\Entity;

use App\Repository\ColorIdentityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ColorIdentityRepository::class)]
class ColorIdentity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private string $name;

    /** @var Collection<int, Color> */
    #[ORM\ManyToMany(targetEntity: Color::class)]
    #[ORM\JoinTable(name: 'color_identity_color')]
    private Collection $colors;

    public function __construct()
    {
        $this->colors = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    /** @return Collection<int, Color> */
    public function getColors(): Collection { return $this->colors; }
    public function __toString(): string { return $this->name; }
}
