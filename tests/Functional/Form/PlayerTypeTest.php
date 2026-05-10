<?php

namespace App\Tests\Functional\Form;

use App\Entity\Player;
use App\Form\PlayerType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

#[AllowMockObjectsWithoutExpectations]
class PlayerTypeTest extends TypeTestCase
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
        $form = $this->factory->create(PlayerType::class);
        $this->assertTrue($form->has('username'));
        $this->assertTrue($form->has('email'));
    }

    public function testValidDataCreatesPlayer(): void
    {
        $form = $this->factory->create(PlayerType::class);
        $form->submit(['username' => 'alice', 'email' => 'alice@example.com']);

        $this->assertTrue($form->isValid());
        $player = $form->getData();
        $this->assertInstanceOf(Player::class, $player);
        $this->assertSame('alice', $player->getUsername());
        $this->assertSame('alice@example.com', $player->getEmail());
    }

    public function testEmptyUsernameIsInvalid(): void
    {
        $form = $this->factory->create(PlayerType::class);
        $form->submit(['username' => '', 'email' => 'alice@example.com']);

        $this->assertFalse($form->isValid());
    }

    public function testInvalidEmailIsInvalid(): void
    {
        $form = $this->factory->create(PlayerType::class);
        $form->submit(['username' => 'alice', 'email' => 'not-an-email']);

        $this->assertFalse($form->isValid());
    }

    public function testDataClassIsPlayer(): void
    {
        $form = $this->factory->create(PlayerType::class);
        $form->submit(['username' => 'bob', 'email' => 'bob@example.com']);

        $this->assertInstanceOf(Player::class, $form->getData());
    }

    public function testBothFieldsRequired(): void
    {
        $form = $this->factory->create(PlayerType::class);
        $form->submit(['username' => '', 'email' => '']);

        $this->assertFalse($form->isValid());
        $errors = $form->getErrors(true);
        $this->assertGreaterThanOrEqual(1, iterator_count($errors));
    }
}
