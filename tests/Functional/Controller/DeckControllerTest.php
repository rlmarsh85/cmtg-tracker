<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Deck;
use App\Entity\Player;
use App\Repository\DeckRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DeckControllerTest extends WebTestCase
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

    private function seedPlayer(): Player
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $player = new Player();
        $player->setUsername('deckowner');
        $player->setEmail('deckowner@example.com');
        $em->persist($player);
        $em->flush();
        return $player;
    }

    private function seedDeck(Player $player): Deck
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $deck = new Deck();
        $deck->setName('Test Deck');
        $deck->setFormat('Commander');
        $deck->setPlayer($player);
        $em->persist($deck);
        $em->flush();
        return $deck;
    }

    public function testIndexReturns200(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/decks');
        $this->assertResponseIsSuccessful();
    }

    public function testNewPageReturns200(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/decks/new');
        $this->assertResponseIsSuccessful();
    }

    public function testCreateDeckRedirectsOnSuccess(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();

        $crawler = $client->request('GET', '/decks/new');
        $form = $crawler->selectButton('Save')->form([
            'deck[name]'   => 'My Elf Deck',
            'deck[format]' => 'Commander',
            'deck[player]' => (string) $player->getId(),
        ]);
        $client->submit($form);
        $this->assertResponseRedirects('/decks');
    }

    public function testCreateDeckPersistsDeck(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();

        $crawler = $client->request('GET', '/decks/new');
        $form = $crawler->selectButton('Save')->form([
            'deck[name]'   => 'Persisted Deck',
            'deck[format]' => 'Modern',
            'deck[player]' => (string) $player->getId(),
        ]);
        $client->submit($form);

        $repo = static::getContainer()->get(DeckRepository::class);
        $deck = $repo->findOneBy(['name' => 'Persisted Deck']);
        $this->assertNotNull($deck);
        $this->assertSame('Modern', $deck->getFormat());
    }

    public function testCreateDeckInvalidDataShowsErrors(): void
    {
        $client = $this->bootWithSchema();
        $crawler = $client->request('GET', '/decks/new');
        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();
        $formValues['deck']['format'] = 'NotARealFormat';
        $client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testShowReturns200(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $deck = $this->seedDeck($player);
        $client->request('GET', '/decks/' . $deck->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testShowReturns404ForMissingDeck(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/decks/99999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditReturns200(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $deck = $this->seedDeck($player);
        $client->request('GET', '/decks/' . $deck->getId() . '/edit');
        $this->assertResponseIsSuccessful();
    }

    public function testEditDeckUpdatesEntity(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $deck = $this->seedDeck($player);
        $id = $deck->getId();

        $crawler = $client->request('GET', '/decks/' . $id . '/edit');
        $form = $crawler->selectButton('Save')->form([
            'deck[name]'   => 'Updated Deck Name',
            'deck[format]' => 'Legacy',
            'deck[player]' => (string) $player->getId(),
        ]);
        $client->submit($form);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $updated = $em->find(Deck::class, $id);
        $this->assertSame('Updated Deck Name', $updated->getName());
        $this->assertSame('Legacy', $updated->getFormat());
    }

    public function testDeleteWithValidCsrfToken(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $deck = $this->seedDeck($player);
        $id = $deck->getId();

        $crawler = $client->request('GET', '/decks/' . $id);
        $token = $crawler->filter('input[name="_token"]')->attr('value');

        $client->request('POST', '/decks/' . $id . '/delete', ['_token' => $token]);
        $this->assertResponseRedirects('/decks');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->assertNull($em->find(Deck::class, $id));
    }

    public function testDeleteWithInvalidCsrfToken(): void
    {
        $client = $this->bootWithSchema();
        $player = $this->seedPlayer();
        $deck = $this->seedDeck($player);
        $id = $deck->getId();

        $client->request('POST', '/decks/' . $id . '/delete', ['_token' => 'wrong-token']);
        $this->assertResponseRedirects('/decks');

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $this->assertNotNull($em->find(Deck::class, $id));
    }
}
