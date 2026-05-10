<?php

namespace App\Tests\Functional\Form;

use App\Entity\Deck;
use App\Entity\Player;
use App\Form\DeckType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class DeckTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);
    }

    private function makePlayer(string $username): Player
    {
        $player = new Player();
        $player->setUsername($username);
        $player->setEmail($username . '@example.com');
        $this->em->persist($player);
        $this->em->flush();
        return $player;
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->formFactory->create(DeckType::class);
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('format'));
        $this->assertTrue($form->has('colors'));
        $this->assertTrue($form->has('commander'));
        $this->assertTrue($form->has('partner'));
        $this->assertTrue($form->has('notes'));
        $this->assertTrue($form->has('player'));
    }

    public function testValidDataSetsName(): void
    {
        $player = $this->makePlayer('alice');
        $form = $this->formFactory->create(DeckType::class);
        $form->submit([
            'name'   => 'My Test Deck',
            'format' => 'Commander',
            'player' => (string) $player->getId(),
        ]);

        $deck = $form->getData();
        $this->assertInstanceOf(Deck::class, $deck);
        $this->assertSame('My Test Deck', $deck->getName());
    }

    public function testDefaultFormatIsCommander(): void
    {
        $player = $this->makePlayer('bob');
        $form = $this->formFactory->create(DeckType::class, new Deck());
        $deck = $form->getData();
        $this->assertSame('Commander', $deck->getFormat());
    }

    public function testColorsFieldIsUnmapped(): void
    {
        $form = $this->formFactory->create(DeckType::class);
        $this->assertFalse($form->get('colors')->getConfig()->getMapped());
    }

    public function testCommanderFieldIsUnmapped(): void
    {
        $form = $this->formFactory->create(DeckType::class);
        $this->assertFalse($form->get('commander')->getConfig()->getMapped());
    }

    public function testPartnerFieldIsUnmapped(): void
    {
        $form = $this->formFactory->create(DeckType::class);
        $this->assertFalse($form->get('partner')->getConfig()->getMapped());
    }

    public function testNotesIsOptional(): void
    {
        $player = $this->makePlayer('charlie');
        $form = $this->formFactory->create(DeckType::class);
        $form->submit([
            'name'   => 'No Notes Deck',
            'format' => 'Modern',
            'player' => (string) $player->getId(),
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertNull($form->getData()->getNotes());
    }

    public function testFormatChoicesMatchDeckFormatsConstant(): void
    {
        $form = $this->formFactory->create(DeckType::class);
        $formatField = $form->get('format');
        $choices = $formatField->getConfig()->getOption('choices');

        foreach (Deck::FORMATS as $format) {
            $this->assertArrayHasKey($format, $choices, "Format '$format' missing from choices");
        }
        $this->assertCount(count(Deck::FORMATS), $choices);
    }

    public function testHiddenFieldsAreUnmapped(): void
    {
        $form = $this->formFactory->create(DeckType::class);
        $this->assertFalse($form->get('commander_colors')->getConfig()->getMapped());
        $this->assertFalse($form->get('commander_partner_type')->getConfig()->getMapped());
        $this->assertFalse($form->get('commander_partner_with')->getConfig()->getMapped());
        $this->assertFalse($form->get('commander_image_uri')->getConfig()->getMapped());
        $this->assertFalse($form->get('partner_colors')->getConfig()->getMapped());
        $this->assertFalse($form->get('partner_image_uri')->getConfig()->getMapped());
    }
}
