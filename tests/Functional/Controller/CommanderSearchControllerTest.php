<?php

namespace App\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CommanderSearchControllerTest extends WebTestCase
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

    private function mockHttp(MockResponse|array|callable $responses): void
    {
        $container = static::getContainer();
        $container->set(HttpClientInterface::class, new MockHttpClient($responses));
        /** @var \Psr\Cache\CacheItemPoolInterface $cache */
        $cache = $container->get('cache.app');
        $cache->clear();
    }

    private function scryfallCard(string $name, array $keywords = [], array $colorIdentity = []): array
    {
        return [
            'name'           => $name,
            'color_identity' => $colorIdentity,
            'keywords'       => $keywords,
            'oracle_text'    => '',
            'image_uris'     => ['normal' => 'https://img/' . md5($name) . '.jpg'],
        ];
    }

    private function scryfallResponse(array $cards, int $status = 200): MockResponse
    {
        return new MockResponse(
            json_encode(['data' => $cards, 'total_cards' => count($cards)]),
            ['http_code' => $status, 'response_headers' => ['Content-Type: application/json']]
        );
    }

    // ─── search endpoint ─────────────────────────────────────────────────────

    public function testSearchReturnsEmptyArrayForShortQuery(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/api/commanders/search?q=a');
        $this->assertResponseIsSuccessful();
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testSearchReturnsEmptyArrayForEmptyQuery(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/api/commanders/search');
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testSearchReturnsResultsFromMockedScryfall(): void
    {
        $client = $this->bootWithSchema();
        $this->mockHttp($this->scryfallResponse([
            $this->scryfallCard('Breya, Etherium Shaper', [], ['W', 'U', 'B', 'R']),
            $this->scryfallCard('Bruse Tarl, Boorish Herder', ['partner'], ['R', 'W']),
        ]));

        $client->request('GET', '/api/commanders/search?q=breya');
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
        $this->assertSame('Breya, Etherium Shaper', $data[0]['name']);
    }

    public function testSearchReturnsEmptyOnScryfallNon200(): void
    {
        $client = $this->bootWithSchema();
        $this->mockHttp(new MockResponse('{"error":"not_found"}', ['http_code' => 404]));

        $client->request('GET', '/api/commanders/search?q=unknownxyz');
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testSearchReturnsEmptyOnException(): void
    {
        $client = $this->bootWithSchema();
        $this->mockHttp(new MockResponse('', ['error' => 'Network error']));

        $client->request('GET', '/api/commanders/search?q=brokenquery');
        $this->assertResponseIsSuccessful();
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testSearchResultsLimitedToTwelve(): void
    {
        $client = $this->bootWithSchema();
        $cards = array_map(fn($i) => $this->scryfallCard("Commander $i"), range(1, 20));
        $this->mockHttp($this->scryfallResponse($cards));

        $client->request('GET', '/api/commanders/search?q=commander');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(12, $data);
    }

    public function testSearchResultStructure(): void
    {
        $client = $this->bootWithSchema();
        $this->mockHttp($this->scryfallResponse([$this->scryfallCard('Atraxa', [], ['W', 'U', 'B', 'G'])]));

        $client->request('GET', '/api/commanders/search?q=atraxa');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $row = $data[0];
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('color_identity', $row);
        $this->assertArrayHasKey('partner_type', $row);
        $this->assertArrayHasKey('partner_with', $row);
        $this->assertArrayHasKey('image_uri', $row);
    }

    // ─── partnerSearch endpoint ───────────────────────────────────────────────

    public function testPartnerSearchReturnsEmptyForShortQuery(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/api/commanders/partners?q=x&type=partner');
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testPartnerSearchReturnsEmptyForPartnerWithType(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/api/commanders/partners?q=breya&type=partner_with');
        $this->assertSame([], json_decode($client->getResponse()->getContent(), true));
    }

    public function testPartnerSearchFriendsForeverFilter(): void
    {
        $client = $this->bootWithSchema();
        $requestedUrl = null;
        $this->mockHttp(function (string $method, string $url, array $options = []) use (&$requestedUrl) {
            $requestedUrl = $url . '?' . http_build_query($options['query'] ?? []);
            return new MockResponse(json_encode(['data' => []]), ['http_code' => 200]);
        });

        $client->request('GET', '/api/commanders/partners?q=bruse&type=friends_forever');
        $this->assertStringContainsString('friends forever', urldecode($requestedUrl ?? ''));
    }

    public function testPartnerSearchChooseBackgroundFilter(): void
    {
        $client = $this->bootWithSchema();
        $requestedUrl = null;
        $this->mockHttp(function (string $method, string $url, array $options = []) use (&$requestedUrl) {
            $requestedUrl = $url . '?' . http_build_query($options['query'] ?? []);
            return new MockResponse(json_encode(['data' => []]), ['http_code' => 200]);
        });

        $client->request('GET', '/api/commanders/partners?q=bruse&type=choose_background');
        $this->assertStringContainsString('background', urldecode($requestedUrl ?? ''));
    }

    public function testPartnerSearchDoctorsCompanionFilter(): void
    {
        $client = $this->bootWithSchema();
        $requestedUrl = null;
        $this->mockHttp(function (string $method, string $url, array $options = []) use (&$requestedUrl) {
            $requestedUrl = $url . '?' . http_build_query($options['query'] ?? []);
            return new MockResponse(json_encode(['data' => []]), ['http_code' => 200]);
        });

        $client->request('GET', '/api/commanders/partners?q=bruse&type=doctors_companion');
        $this->assertStringContainsString("doctor", urldecode($requestedUrl ?? ''));
    }

    // ─── commanderInfo endpoint ───────────────────────────────────────────────

    public function testCommanderInfoReturns404ForEmptyName(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/api/commanders/info');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCommanderInfoReturns404WhenScryfallMisses(): void
    {
        $client = $this->bootWithSchema();
        $this->mockHttp(new MockResponse('{"error":"not_found"}', ['http_code' => 404]));

        $client->request('GET', '/api/commanders/info?name=NoSuchCard');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCommanderInfoReturnsCardData(): void
    {
        $client = $this->bootWithSchema();
        $card = array_merge($this->scryfallCard('Atraxa, Praetors\' Voice', [], ['W', 'U', 'B', 'G']), ['oracle_text' => 'Flying, vigilance, deathtouch, lifelink. Proliferate.']);
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=' . urlencode('Atraxa, Praetors\' Voice'));
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Atraxa, Praetors\' Voice', $data['name']);
    }

    public function testCommanderInfoResultStructure(): void
    {
        $client = $this->bootWithSchema();
        $card = $this->scryfallCard('Breya', [], ['W', 'U', 'B', 'R']);
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=Breya');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('color_identity', $data);
        $this->assertArrayHasKey('partner_type', $data);
        $this->assertArrayHasKey('partner_with', $data);
        $this->assertArrayHasKey('image_uri', $data);
    }

    // ─── extractImageUri / extractPartnerInfo (tested via endpoints) ──────────

    public function testExtractImageUriUsesTopLevelImageUris(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'Double Card',
            'color_identity' => [],
            'keywords'       => [],
            'oracle_text'    => '',
            'image_uris'     => ['normal' => 'https://img/top-level.jpg'],
            'card_faces'     => [
                ['image_uris' => ['normal' => 'https://img/face0.jpg'], 'keywords' => [], 'oracle_text' => ''],
            ],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=Double+Card');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('https://img/top-level.jpg', $data['image_uri']);
    }

    public function testExtractImageUriFallsBackToCardFaces(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'DFC Card',
            'color_identity' => [],
            'keywords'       => [],
            'oracle_text'    => '',
            'card_faces'     => [
                ['image_uris' => ['normal' => 'https://img/face0.jpg'], 'keywords' => [], 'oracle_text' => ''],
                ['image_uris' => ['normal' => 'https://img/face1.jpg'], 'keywords' => [], 'oracle_text' => ''],
            ],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=DFC+Card');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('https://img/face0.jpg', $data['image_uri']);
    }

    public function testExtractPartnerInfoDetectsPartnerWith(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'Rowan Kenrith',
            'color_identity' => ['R'],
            'keywords'       => [],
            'oracle_text'    => 'Partner with Will Kenrith (When this creature enters the battlefield, target player may put Will Kenrith into their hand from their library, then shuffle.)',
            'image_uris'     => ['normal' => 'https://img/rowan.jpg'],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=Rowan+Kenrith');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('partner_with', $data['partner_type']);
        $this->assertSame('Will Kenrith', $data['partner_with']);
    }

    public function testExtractPartnerInfoDetectsGenericPartner(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'Bruse Tarl, Boorish Herder',
            'color_identity' => ['R', 'W'],
            'keywords'       => ['partner'],
            'oracle_text'    => 'Partner',
            'image_uris'     => ['normal' => 'https://img/bruse.jpg'],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=Bruse+Tarl');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('partner', $data['partner_type']);
        $this->assertNull($data['partner_with']);
    }

    public function testExtractPartnerInfoDetectsFriendsForever(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'Luke Skywalker',
            'color_identity' => ['W'],
            'keywords'       => ['friends forever'],
            'oracle_text'    => 'Friends forever',
            'image_uris'     => ['normal' => 'https://img/luke.jpg'],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=Luke+Skywalker');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('friends_forever', $data['partner_type']);
    }

    public function testExtractPartnerInfoDetectsChooseBackground(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'Ambitious Farmhand',
            'color_identity' => ['W'],
            'keywords'       => ['choose a background'],
            'oracle_text'    => 'Choose a Background',
            'image_uris'     => ['normal' => 'https://img/farmhand.jpg'],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=Ambitious+Farmhand');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('choose_background', $data['partner_type']);
    }

    public function testExtractPartnerInfoDetectsDoctorsCompanion(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'River Song',
            'color_identity' => ['U', 'R'],
            'keywords'       => ["doctor's companion"],
            'oracle_text'    => "Doctor's companion",
            'image_uris'     => ['normal' => 'https://img/river.jpg'],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=River+Song');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('doctors_companion', $data['partner_type']);
    }

    public function testExtractPartnerInfoMergesKeywordsFromCardFaces(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'DFC Partner',
            'color_identity' => ['R'],
            'keywords'       => [],
            'oracle_text'    => '',
            'card_faces'     => [
                ['keywords' => ['partner'], 'oracle_text' => 'Partner', 'image_uris' => ['normal' => 'https://img/face.jpg']],
                ['keywords' => [], 'oracle_text' => ''],
            ],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=DFC+Partner');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('partner', $data['partner_type']);
    }

    public function testExtractPartnerInfoReturnsNullForNonPartner(): void
    {
        $client = $this->bootWithSchema();
        $card = [
            'name'           => 'Plain Commander',
            'color_identity' => ['G'],
            'keywords'       => [],
            'oracle_text'    => 'Trample.',
            'image_uris'     => ['normal' => 'https://img/plain.jpg'],
        ];
        $this->mockHttp(new MockResponse(json_encode($card), ['http_code' => 200]));

        $client->request('GET', '/api/commanders/info?name=Plain+Commander');
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertNull($data['partner_type']);
        $this->assertNull($data['partner_with']);
    }
}
