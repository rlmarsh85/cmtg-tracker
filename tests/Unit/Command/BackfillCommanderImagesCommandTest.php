<?php

namespace App\Tests\Unit\Command;

use App\Command\BackfillCommanderImagesCommand;
use App\Entity\Commander;
use App\Repository\CommanderRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[AllowMockObjectsWithoutExpectations]
class BackfillCommanderImagesCommandTest extends TestCase
{
    private CommanderRepository&MockObject $repo;
    private EntityManagerInterface&MockObject $em;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(CommanderRepository::class);
        $this->em   = $this->createMock(EntityManagerInterface::class);
    }

    private function buildTester(array $mockResponses): CommandTester
    {
        $httpClient = new MockHttpClient($mockResponses);
        $command = new BackfillCommanderImagesCommand($this->repo, $this->em, $httpClient);
        return new CommandTester($command);
    }

    private function makeCommander(string $name): Commander&MockObject
    {
        $commander = $this->createMock(Commander::class);
        $commander->method('getName')->willReturn($name);
        return $commander;
    }

    public function testExecuteWithNoCommandersReturnsSuccess(): void
    {
        $this->repo->method('findBy')->with(['imageUri' => null])->willReturn([]);
        $this->em->expects($this->never())->method('flush');

        $tester = $this->buildTester([]);
        $exitCode = $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteUpdatesImageUriOnSuccessfulScryfallResponse(): void
    {
        $commander = $this->makeCommander('Atraxa, Praetors\' Voice');

        $responseBody = json_encode([
            'name'        => 'Atraxa, Praetors\' Voice',
            'image_uris'  => ['normal' => 'https://img/atraxa.jpg'],
        ]);
        $mockResponse = new MockResponse($responseBody, ['http_code' => 200]);

        $this->repo->method('findBy')->willReturn([$commander]);
        $commander->expects($this->once())->method('setImageUri')->with('https://img/atraxa.jpg');
        $this->em->expects($this->once())->method('flush');

        $tester = $this->buildTester([$mockResponse]);
        $exitCode = $tester->execute([]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteHandlesCardFacesImageFallback(): void
    {
        $commander = $this->makeCommander('Delver of Secrets');

        $responseBody = json_encode([
            'name'       => 'Delver of Secrets',
            'card_faces' => [
                ['image_uris' => ['normal' => 'https://img/delver.jpg']],
                ['image_uris' => ['normal' => 'https://img/insectile.jpg']],
            ],
        ]);
        $mockResponse = new MockResponse($responseBody, ['http_code' => 200]);

        $this->repo->method('findBy')->willReturn([$commander]);
        $commander->expects($this->once())->method('setImageUri')->with('https://img/delver.jpg');
        $this->em->method('flush');

        $tester = $this->buildTester([$mockResponse]);
        $tester->execute([]);
    }

    public function testExecuteSkipsCommanderWhenScryfallReturnsNon200(): void
    {
        $commander = $this->makeCommander('Unknown Commander');
        $mockResponse = new MockResponse('{"error":"not found"}', ['http_code' => 404]);

        $this->repo->method('findBy')->willReturn([$commander]);
        $commander->expects($this->never())->method('setImageUri');
        $this->em->expects($this->once())->method('flush');

        $tester = $this->buildTester([$mockResponse]);
        $exitCode = $tester->execute([]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    public function testExecuteSkipsCommanderWhenNoImageFoundInResponse(): void
    {
        $commander = $this->makeCommander('Cardboard Man');

        $responseBody = json_encode(['name' => 'Cardboard Man']);
        $mockResponse = new MockResponse($responseBody, ['http_code' => 200]);

        $this->repo->method('findBy')->willReturn([$commander]);
        $commander->expects($this->never())->method('setImageUri');
        $this->em->method('flush');

        $tester = $this->buildTester([$mockResponse]);
        $tester->execute([]);
    }

    public function testExecuteFlushesOnceAfterAllCommanders(): void
    {
        $commander1 = $this->makeCommander('Commander One');
        $commander2 = $this->makeCommander('Commander Two');

        $makeResponse = fn() => new MockResponse(
            json_encode(['name' => 'X', 'image_uris' => ['normal' => 'https://img/x.jpg']]),
            ['http_code' => 200]
        );

        $this->repo->method('findBy')->willReturn([$commander1, $commander2]);
        $commander1->method('setImageUri');
        $commander2->method('setImageUri');
        $this->em->expects($this->once())->method('flush');

        $tester = $this->buildTester([$makeResponse(), $makeResponse()]);
        $tester->execute([]);
    }

    public function testExecuteReturnsSuccessCode(): void
    {
        $this->repo->method('findBy')->willReturn([]);

        $tester = $this->buildTester([]);
        $exitCode = $tester->execute([]);
        $this->assertSame(0, $exitCode);
    }

    public function testExecuteSkipsCommanderOnNetworkException(): void
    {
        $commander = $this->makeCommander('Network Fail');

        $throwingResponse = new MockResponse('', [
            'error' => 'Connection timed out',
        ]);

        $this->repo->method('findBy')->willReturn([$commander]);
        $commander->expects($this->never())->method('setImageUri');
        $this->em->expects($this->once())->method('flush');

        $tester = $this->buildTester([$throwingResponse]);
        $exitCode = $tester->execute([]);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
