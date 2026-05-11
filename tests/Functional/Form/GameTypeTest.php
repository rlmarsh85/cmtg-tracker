<?php

namespace App\Tests\Functional\Form;

use App\Entity\Deck;
use App\Entity\Game;
use App\Form\GameType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[AllowMockObjectsWithoutExpectations]
class GameTypeTest extends TypeTestCase
{
    protected function getExtensions(): array
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        return [new ValidatorExtension($validator)];
    }

    public function testFormFieldsExist(): void
    {
        $form = $this->factory->create(GameType::class);
        $this->assertTrue($form->has('playedAt'));
        $this->assertTrue($form->has('format'));
        $this->assertTrue($form->has('notes'));
        $this->assertTrue($form->has('turnCount'));
    }

    public function testValidDataCreatesGame(): void
    {
        $form = $this->factory->create(GameType::class);
        $form->submit(['playedAt' => '2024-01-15', 'format' => 'Commander', 'notes' => '']);

        $this->assertTrue($form->isSynchronized());
        $game = $form->getData();
        $this->assertInstanceOf(Game::class, $game);
        $this->assertSame('2024-01-15', $game->getPlayedAt()->format('Y-m-d'));
        $this->assertSame('Commander', $game->getFormat());
    }

    public function testDefaultFormatIsCommander(): void
    {
        $form = $this->factory->create(GameType::class);
        $form->submit(['playedAt' => '2024-01-15', 'format' => 'Commander']);

        $game = $form->getData();
        $this->assertSame('Commander', $game->getFormat());
    }

    public function testNotesIsOptional(): void
    {
        $form = $this->factory->create(GameType::class);
        $form->submit(['playedAt' => '2024-03-01', 'format' => 'Modern']);

        $this->assertTrue($form->isSynchronized());
    }

    public function testInvalidFormatIsRejected(): void
    {
        $form = $this->factory->create(GameType::class);
        $form->submit(['playedAt' => '2024-01-15', 'format' => 'InvalidFormat']);

        $this->assertFalse($form->isValid());
    }

    public function testDataClassIsGame(): void
    {
        $form = $this->factory->create(GameType::class);
        $form->submit(['playedAt' => '2024-01-15', 'format' => 'Commander']);

        $this->assertInstanceOf(Game::class, $form->getData());
    }

    public function testAllFormatsAreValid(): void
    {
        foreach (Deck::FORMATS as $format) {
            $form = $this->factory->create(GameType::class);
            $form->submit(['playedAt' => '2024-01-15', 'format' => $format]);
            $this->assertTrue($form->isValid(), "Format '{$format}' should be valid");
        }
    }

    public function testTurnCountIsOptional(): void
    {
        $form = $this->factory->create(GameType::class);
        $form->submit(['playedAt' => '2024-01-15', 'format' => 'Commander']);

        $this->assertTrue($form->isSynchronized());
        $this->assertNull($form->getData()->getTurnCount());
    }

    public function testTurnCountIsSavedWhenProvided(): void
    {
        $form = $this->factory->create(GameType::class);
        $form->submit(['playedAt' => '2024-01-15', 'format' => 'Commander', 'turnCount' => '15']);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame(15, $form->getData()->getTurnCount());
    }

    public function testNegativeTurnCountIsInvalid(): void
    {
        $form = $this->factory->create(GameType::class);
        $form->submit(['playedAt' => '2024-01-15', 'format' => 'Commander', 'turnCount' => '-1']);

        $this->assertFalse($form->isValid());
    }
}
