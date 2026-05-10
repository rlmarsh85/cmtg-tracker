<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Player> */
class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    /** @return list<array{0: Player, totalGames: int<0, max>, wins: int}> */
    public function findWithStats(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.gamePlayers', 'gp')
            ->addSelect('COUNT(gp.id) as totalGames', 'SUM(CASE WHEN gp.winner = true THEN 1 ELSE 0 END) as wins')
            ->groupBy('p.id')
            ->orderBy('p.username', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
