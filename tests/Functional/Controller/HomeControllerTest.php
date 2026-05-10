<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Player;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
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

    public function testHomePageReturns200(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
    }

    public function testHomePageWithEmptyDatabase(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
    }

    public function testHomePageRendersBody(): void
    {
        $client = $this->bootWithSchema();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }
}
