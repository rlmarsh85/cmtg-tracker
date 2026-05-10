<?php

namespace App\Tests\Integration\Repository;

use App\Entity\Color;
use App\Entity\ColorIdentity;
use App\Repository\ColorIdentityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ColorIdentityRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ColorIdentityRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        $this->repo = self::getContainer()->get(ColorIdentityRepository::class);
    }

    private function makeColor(string $name): Color
    {
        $color = new Color();
        $color->setName($name);
        $this->em->persist($color);
        return $color;
    }

    private function makeColorIdentity(string $name, array $colors): ColorIdentity
    {
        $ci = new ColorIdentity();
        $ci->setName($name);
        foreach ($colors as $color) {
            $ci->getColors()->add($color);
        }
        $this->em->persist($ci);
        return $ci;
    }

    public function testFindByColorNamesReturnsMatchingIdentity(): void
    {
        $blue = $this->makeColor('Blue');
        $red  = $this->makeColor('Red');
        $ci   = $this->makeColorIdentity('Izzet', [$blue, $red]);
        $this->em->flush();

        $result = $this->repo->findByColorNames(['Blue', 'Red']);
        $this->assertSame($ci, $result);
    }

    public function testFindByColorNamesSortOrderIsIrrelevant(): void
    {
        $blue = $this->makeColor('Blue');
        $red  = $this->makeColor('Red');
        $ci   = $this->makeColorIdentity('Izzet', [$blue, $red]);
        $this->em->flush();

        $result = $this->repo->findByColorNames(['Red', 'Blue']);
        $this->assertSame($ci, $result);
    }

    public function testFindByColorNamesReturnsNullWhenNoMatch(): void
    {
        $blue = $this->makeColor('Blue');
        $red  = $this->makeColor('Red');
        $this->makeColorIdentity('Izzet', [$blue, $red]);
        $this->em->flush();

        $result = $this->repo->findByColorNames(['White', 'Red', 'Blue']);
        $this->assertNull($result);
    }

    public function testFindByColorNamesWithEmptyArrayReturnsNull(): void
    {
        $blue = $this->makeColor('Blue');
        $red  = $this->makeColor('Red');
        $this->makeColorIdentity('Izzet', [$blue, $red]);
        $this->em->flush();

        $result = $this->repo->findByColorNames([]);
        $this->assertNull($result);
    }

    public function testFindByColorNamesDoesNotReturnPartialMatch(): void
    {
        $blue  = $this->makeColor('Blue');
        $black = $this->makeColor('Black');
        $this->makeColorIdentity('Dimir', [$blue, $black]);
        $this->em->flush();

        $result = $this->repo->findByColorNames(['Blue']);
        $this->assertNull($result);
    }

    public function testFindByColorNamesWithSingleColor(): void
    {
        $white = $this->makeColor('White');
        $ci    = $this->makeColorIdentity('White', [$white]);
        $this->em->flush();

        $result = $this->repo->findByColorNames(['White']);
        $this->assertSame($ci, $result);
    }
}
