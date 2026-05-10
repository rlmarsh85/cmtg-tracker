<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Player;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GameControllerTest extends WebTestCase
{
    private function bootWithSchema(): KernelBrowser
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($em);
        $meta = $em->getMetadataFactory()->getAllMetadata();
        $tool->dropSchema($meta);
        $tool->createSchema($meta);
        return $client;
    }

    private function seedPlayer(string $username = 'gameplayer', string $email = 'gp@example.com'): Player
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $player = new Player();
        $player->setUsername($username);
        $player->setEmail($email);
        $em->persist($player);
        $em->flush();
        return $player;
    }

    private function seedGame(): Game
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $game = new Game();
        $game->setPlayedAt(new \DateTimeImmutable('2024-01-15'));
        $em->persist($game);
        $em->flush();
        return $game;
    }

    public function testIndexReturns200(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/games');
        $this->assertResponseIsSuccessful();
    }

    public function testNewPageReturns200(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/games/new');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateGameWithNoParticipantsRedirects(): void
    {
        $client = $this->bootWithSchema();
        $crawler = $client->request('GET', '/games/new');
        $form = $crawler->selectButton('Save')->form([
            'game[playedAt]' => '2024-01-15',
            'game[format]'   => 'Commander',
        ]);
        $client->submit($form);
        $this->assertResponseRedirects();
    }

    public function testCreateGameWithParticipantsCreatesGamePlayers(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();

        $crawler = $client->request('GET', '/games/new');
        $form = $crawler->selectButton('Save')->form([
            'game[playedAt]' => '2024-03-10',
            'game[format]'   => 'Commander',
        ]);
        $formValues = $form->getPhpValues();
        $formValues['participants'] = [
            ['player_id' => (string) $player->getId(), 'winner' => '1', 'placement' => '1'],
        ];
        $client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertResponseRedirects();

        $repo = static::getContainer()->get(GameRepository::class);
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $games = $repo->findAll();
        $this->assertCount(1, $games);
        $this->assertCount(1, $games[0]->getParticipants());
    }

    public function testCreateGameSkipsInvalidParticipantPlayerId(): void
    {
        $client = $this->bootWithSchema();
        $crawler = $client->request('GET', '/games/new');
        $form = $crawler->selectButton('Save')->form([
            'game[playedAt]' => '2024-01-15',
            'game[format]'   => 'Commander',
        ]);
        $formValues = $form->getPhpValues();
        $formValues['participants'] = [
            ['player_id' => '99999', 'winner' => '0', 'placement' => ''],
        ];
        $client->request($form->getMethod(), $form->getUri(), $formValues);

        $this->assertResponseRedirects();

        $repo = static::getContainer()->get(GameRepository::class);
        $games = $repo->findAll();
        $this->assertCount(1, $games);
        $this->assertCount(0, $games[0]->getParticipants());
    }

    public function testShowReturns200(): void
    {
        $client = $this->bootWithSchema();
        $game = $this->seedGame();
        $client->request('GET', '/games/' . $game->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testShowReturns404ForMissingGame(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/games/99999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditReturns200(): void
    {
        $client = $this->bootWithSchema();
        $game = $this->seedGame();
        $client->request('GET', '/games/' . $game->getId() . '/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditGameUpdatesFormat(): void
    {
        $client = $this->bootWithSchema();
        $game = $this->seedGame();
        $id = $game->getId();

        $crawler = $client->request('GET', '/games/' . $id . '/edit');
        $form = $crawler->selectButton('Save')->form([
            'game[playedAt]' => '2024-06-01',
            'game[format]'   => 'Legacy',
        ]);
        $client->submit($form);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $updated = $em->find(Game::class, $id);
        $this->assertSame('Legacy', $updated->getFormat());
    }

    public function testEditGameRebuildsParticipants(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $game = $this->seedGame();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $gp = new GamePlayer();
        $gp->setPlayer($player);
        $gp->setGame($game);
        $game->addParticipant($gp);
        $em->persist($gp);
        $em->flush();

        $crawler = $client->request('GET', '/games/' . $game->getId() . '/edit');
        $form = $crawler->selectButton('Save')->form([
            'game[playedAt]' => '2024-01-15',
            'game[format]'   => 'Commander',
        ]);
        $formValues = $form->getPhpValues();
        unset($formValues['participants']);
        $client->request($form->getMethod(), $form->getUri(), $formValues);

        $em->clear();
        $updated = $em->find(Game::class, $game->getId());
        $this->assertCount(0, $updated->getParticipants());
    }

    public function testDeleteWithValidCsrfToken(): void
    {
        $client = $this->bootWithSchema();
        $game = $this->seedGame();
        $id = $game->getId();

        $crawler = $client->request('GET', '/games/' . $id);
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/games/' . $id . '/delete', ['_token' => $token]);
        $this->assertResponseRedirects('/games');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->assertNull($em->find(Game::class, $id));
    }

    public function testDeleteWithInvalidCsrfToken(): void
    {
        $client = $this->bootWithSchema();
        $game = $this->seedGame();
        $id = $game->getId();

        $client->request('POST', '/games/' . $id . '/delete', ['_token' => 'bad-token']);
        $this->assertResponseRedirects('/games');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->assertNotNull($em->find(Game::class, $id));
    }
}
