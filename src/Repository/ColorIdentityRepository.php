<?php

namespace App\Repository;

use App\Entity\ColorIdentity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ColorIdentityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ColorIdentity::class);
    }

    public function findByColorNames(array $colorNames): ?ColorIdentity
    {
        $sorted = $colorNames;
        sort($sorted);

        foreach ($this->findAll() as $identity) {
            $identityColors = $identity->getColors()->map(fn($c) => $c->getName())->toArray();
            sort($identityColors);
            if ($identityColors === $sorted) {
                return $identity;
            }
        }

        return null;
    }
}
