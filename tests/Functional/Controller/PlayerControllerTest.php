<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Player;
use App\Repository\PlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PlayerControllerTest extends WebTestCase
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

    private function seedPlayer(string $username = 'alice', string $email = 'alice@example.com'): Player
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $player = new Player();
        $player->setUsername($username);
        $player->setEmail($email);
        $em->persist($player);
        $em->flush();
        return $player;
    }

    public function testIndexReturns200(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/players');
        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithNoPlayers(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/players');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testNewPageReturns200(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/players/new');
        $this->assertResponseIsSuccessful();
    }

    public function testCreatePlayerRedirectsOnSuccess(): void
    {
        $client = $this->bootWithSchema();
        $crawler = $client->request('GET', '/players/new');
        $form = $crawler->selectButton('Save')->form([
            'player[username]' => 'testuser',
            'player[email]'    => 'testuser@example.com',
        ]);
        $client->submit($form);
        $this->assertResponseRedirects('/players');
    }

    public function testCreatePlayerPersistsEntity(): void
    {
        $client = $this->bootWithSchema();
        $crawler = $client->request('GET', '/players/new');
        $form = $crawler->selectButton('Save')->form([
            'player[username]' => 'newplayer',
            'player[email]'    => 'newplayer@example.com',
        ]);
        $client->submit($form);

        $repo = static::getContainer()->get(PlayerRepository::class);
        $player = $repo->findOneBy(['username' => 'newplayer']);
        $this->assertNotNull($player);
        $this->assertSame('newplayer@example.com', $player->getEmail());
    }

    public function testCreatePlayerInvalidDataShowsErrors(): void
    {
        $client = $this->bootWithSchema();
        $crawler = $client->request('GET', '/players/new');
        $form = $crawler->selectButton('Save')->form([
            'player[username]' => 'Alice',
            'player[email]'    => 'not-a-valid-email',
        ]);
        $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testShowPageReturns200(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $client->request('GET', '/players/' . $player->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testShowPageReturns404ForMissingPlayer(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/players/99999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditPageReturns200(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $client->request('GET', '/players/' . $player->getId() . '/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditPlayerRedirectsOnSuccess(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $crawler = $client->request('GET', '/players/' . $player->getId() . '/edit');
        $form = $crawler->selectButton('Save')->form([
            'player[username]' => 'updatedname',
            'player[email]'    => 'updated@example.com',
        ]);
        $client->submit($form);
        $this->assertResponseRedirects('/players/' . $player->getId());
    }

    public function testEditPlayerUpdatesEntity(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $id = $player->getId();

        $crawler = $client->request('GET', '/players/' . $id . '/edit');
        $form = $crawler->selectButton('Save')->form([
            'player[username]' => 'renameduser',
            'player[email]'    => 'renamed@example.com',
        ]);
        $client->submit($form);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $updated = $em->find(Player::class, $id);
        $this->assertSame('renameduser', $updated->getUsername());
    }

    public function testDeletePlayerWithValidCsrfToken(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $id = $player->getId();

        $crawler = $client->request('GET', '/players/' . $id);
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/players/' . $id . '/delete', ['_token' => $token]);
        $this->assertResponseRedirects('/players');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->assertNull($em->find(Player::class, $id));
    }

    public function testDeletePlayerWithInvalidCsrfToken(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $id = $player->getId();

        $client->request('POST', '/players/' . $id . '/delete', ['_token' => 'invalid-token']);
        $this->assertResponseRedirects('/players');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->assertNotNull($em->find(Player::class, $id));
    }
}
