<?php

namespace App\Tests\Unit\Service;

use App\Entity\ColorIdentity;
use App\Entity\Commander;
use App\Repository\ColorIdentityRepository;
use App\Repository\CommanderRepository;
use App\Service\CommanderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CommanderServiceTest extends TestCase
{
    private CommanderRepository&MockObject $commanderRepo;
    private ColorIdentityRepository&MockObject $colorIdentityRepo;
    private EntityManagerInterface&MockObject $em;
    private CommanderService $service;

    protected function setUp(): void
    {
        $this->commanderRepo      = $this->createMock(CommanderRepository::class);
        $this->colorIdentityRepo  = $this->createMock(ColorIdentityRepository::class);
        $this->em                 = $this->createMock(EntityManagerInterface::class);

        $this->service = new CommanderService(
            $this->commanderRepo,
            $this->colorIdentityRepo,
            $this->em
        );
    }

    public function testFindOrCreateReturnsExistingCommander(): void
    {
        $existing = new Commander();
        $existing->setName('Atraxa');

        $this->commanderRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['name' => 'Atraxa'])
            ->willReturn($existing);

        $this->em->expects($this->never())->method('persist');

        $result = $this->service->findOrCreate('Atraxa');
        $this->assertSame($existing, $result);
    }

    public function testFindOrCreateBackfillsColorIdentityWhenMissing(): void
    {
        $existing = new Commander();
        $existing->setName('Atraxa');

        $ci = $this->createMock(ColorIdentity::class);

        $this->commanderRepo->method('findOneBy')->willReturn($existing);
        // resolveColorIdentity maps letters in input order: W→White, U→Blue
        $this->colorIdentityRepo->expects($this->once())
            ->method('findByColorNames')
            ->with(['White', 'Blue'])
            ->willReturn($ci);

        $this->service->findOrCreate('Atraxa', ['W', 'U']);
        $this->assertSame($ci, $existing->getColorIdentity());
    }

    public function testFindOrCreateColorIdentityMapsLettersToColorNames(): void
    {
        $existing = new Commander();
        $existing->setName('Atraxa');

        $this->commanderRepo->method('findOneBy')->willReturn($existing);
        // R → Red, G → Green — passed in that order to the repository
        $this->colorIdentityRepo->expects($this->once())
            ->method('findByColorNames')
            ->with(['Red', 'Green'])
            ->willReturn(null);

        $this->service->findOrCreate('Atraxa', ['R', 'G']);
    }

    public function testFindOrCreateDoesNotOverrideExistingColorIdentity(): void
    {
        $ci = $this->createMock(ColorIdentity::class);
        $existing = new Commander();
        $existing->setName('Atraxa');
        $existing->setColorIdentity($ci);

        $this->commanderRepo->method('findOneBy')->willReturn($existing);
        $this->colorIdentityRepo->expects($this->never())->method('findByColorNames');

        $this->service->findOrCreate('Atraxa', ['W', 'U', 'B', 'G']);
        $this->assertSame($ci, $existing->getColorIdentity());
    }

    public function testFindOrCreateBackfillsPartnerTypeWhenMissing(): void
    {
        $existing = new Commander();
        $existing->setName('Breya');

        $this->commanderRepo->method('findOneBy')->willReturn($existing);

        $this->service->findOrCreate('Breya', [], 'partner', null, null);
        $this->assertSame('partner', $existing->getPartnerType());
    }

    public function testFindOrCreateDoesNotOverrideExistingPartnerType(): void
    {
        $existing = new Commander();
        $existing->setName('Breya');
        $existing->setPartnerType('friends_forever');

        $this->commanderRepo->method('findOneBy')->willReturn($existing);

        $this->service->findOrCreate('Breya', [], 'partner', null, null);
        $this->assertSame('friends_forever', $existing->getPartnerType());
    }

    public function testFindOrCreateBackfillsImageUriWhenMissing(): void
    {
        $existing = new Commander();
        $existing->setName('Atraxa');

        $this->commanderRepo->method('findOneBy')->willReturn($existing);

        $this->service->findOrCreate('Atraxa', [], null, null, 'https://img/atraxa.jpg');
        $this->assertSame('https://img/atraxa.jpg', $existing->getImageUri());
    }

    public function testFindOrCreateDoesNotOverrideExistingImageUri(): void
    {
        $existing = new Commander();
        $existing->setName('Atraxa');
        $existing->setImageUri('https://img/original.jpg');

        $this->commanderRepo->method('findOneBy')->willReturn($existing);

        $this->service->findOrCreate('Atraxa', [], null, null, 'https://img/new.jpg');
        $this->assertSame('https://img/original.jpg', $existing->getImageUri());
    }

    public function testFindOrCreateCreatesNewCommanderWhenNotFound(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Commander::class));

        $result = $this->service->findOrCreate('New Commander');
        $this->assertInstanceOf(Commander::class, $result);
        $this->assertSame('New Commander', $result->getName());
    }

    public function testFindOrCreateNewCommanderWithNoColorLetters(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->colorIdentityRepo->expects($this->never())->method('findByColorNames');
        $this->em->method('persist');

        $result = $this->service->findOrCreate('New Commander', []);
        $this->assertNull($result->getColorIdentity());
    }

    public function testFindOrCreateNewCommanderWithColorLetters(): void
    {
        $ci = $this->createMock(ColorIdentity::class);
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->colorIdentityRepo->expects($this->once())
            ->method('findByColorNames')
            ->with(['Red', 'Green'])
            ->willReturn($ci);
        $this->em->method('persist');

        $result = $this->service->findOrCreate('New Commander', ['R', 'G']);
        $this->assertSame($ci, $result->getColorIdentity());
    }

    public function testFindOrCreateNewCommanderSetsPartnerType(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->em->method('persist');

        $result = $this->service->findOrCreate('New Commander', [], 'friends_forever');
        $this->assertSame('friends_forever', $result->getPartnerType());
    }

    public function testFindOrCreateNewCommanderSetsPartnerWith(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->em->method('persist');

        $result = $this->service->findOrCreate('New Commander', [], 'partner_with', 'Syr Gwyn');
        $this->assertSame('Syr Gwyn', $result->getPartnerWith());
    }

    public function testFindOrCreateNewCommanderSetsImageUri(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->em->method('persist');

        $result = $this->service->findOrCreate('New Commander', [], null, null, 'https://img/new.jpg');
        $this->assertSame('https://img/new.jpg', $result->getImageUri());
    }

    public function testFindOrCreateEmptyStringPartnerTypeBecomesNull(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->em->method('persist');

        $result = $this->service->findOrCreate('New Commander', [], '');
        $this->assertNull($result->getPartnerType());
    }

    public function testFindOrCreateEmptyStringImageUriBecomesNull(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->em->method('persist');

        $result = $this->service->findOrCreate('New Commander', [], null, null, '');
        $this->assertNull($result->getImageUri());
    }

    public function testFindOrCreateNeverFlushes(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->em->method('persist');
        $this->em->expects($this->never())->method('flush');

        $this->service->findOrCreate('New Commander');
    }

    public function testFindOrCreateIgnoresUnknownColorLetters(): void
    {
        $this->commanderRepo->method('findOneBy')->willReturn(null);
        $this->colorIdentityRepo->expects($this->once())
            ->method('findByColorNames')
            ->with($this->callback(function (array $names) {
                return !in_array('X', $names, true);
            }))
            ->willReturn(null);
        $this->em->method('persist');

        $this->service->findOrCreate('New Commander', ['R', 'X']);
    }

    public function testFindOrCreateBackfillsPartnerWithWhenTypeIsSet(): void
    {
        $existing = new Commander();
        $existing->setName('Breya');

        $this->commanderRepo->method('findOneBy')->willReturn($existing);

        $this->service->findOrCreate('Breya', [], 'partner_with', 'Syr Gwyn');
        $this->assertSame('Syr Gwyn', $existing->getPartnerWith());
    }
}
