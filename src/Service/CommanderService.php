<?php

namespace App\Service;

use App\Entity\ColorIdentity;
use App\Entity\Commander;
use App\Repository\ColorIdentityRepository;
use App\Repository\CommanderRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommanderService
{
    private const COLOR_MAP = ['W' => 'White', 'U' => 'Blue', 'B' => 'Black', 'R' => 'Red', 'G' => 'Green'];

    public function __construct(
        private CommanderRepository $commanderRepository,
        private ColorIdentityRepository $colorIdentityRepository,
        private EntityManagerInterface $em,
    ) {}

    /**
     * Returns an existing Commander by name or creates a new one.
     * Any provided metadata (colors, partner info, image URI) backfills missing values on existing records.
     *
     * @param string[] $colorLetters e.g. ['W', 'B']
     */
    public function findOrCreate(
        string $name,
        array $colorLetters = [],
        ?string $partnerType = null,
        ?string $partnerWith = null,
        ?string $imageUri = null,
    ): Commander {
        $commander = $this->commanderRepository->findOneBy(['name' => $name]);

        if ($commander) {
            if (!empty($colorLetters) && $commander->getColorIdentity() === null) {
                $commander->setColorIdentity($this->resolveColorIdentity($colorLetters));
            }
            if ($partnerType !== null && $commander->getPartnerType() === null) {
                $commander->setPartnerType($partnerType);
                $commander->setPartnerWith($partnerWith ?: null);
            }
            if ($imageUri !== null && $commander->getImageUri() === null) {
                $commander->setImageUri($imageUri);
            }
            return $commander;
        }

        $commander = new Commander();
        $commander->setName($name);
        $commander->setPartnerType($partnerType ?: null);
        $commander->setPartnerWith($partnerWith ?: null);
        $commander->setImageUri($imageUri ?: null);

        if (!empty($colorLetters)) {
            $commander->setColorIdentity($this->resolveColorIdentity($colorLetters));
        }

        $this->em->persist($commander);
        return $commander;
    }

    /** @param list<string> $colorLetters */
    private function resolveColorIdentity(array $colorLetters): ?ColorIdentity
    {
        $names = array_values(array_filter(array_map(
            fn(string $l) => self::COLOR_MAP[$l] ?? null,
            $colorLetters,
        )));
        return $this->colorIdentityRepository->findByColorNames($names);
    }
}
