<?php

namespace App\Form;

use App\Entity\Player;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<Player> */
class PlayerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, ['label' => 'Username', 'attr' => ['class' => 'form-control'], 'empty_data' => ''])
            ->add('email', EmailType::class, ['label' => 'Email', 'attr' => ['class' => 'form-control'], 'empty_data' => ''])
            ->add('nickname', TextType::class, ['label' => 'Nickname', 'attr' => ['class' => 'form-control', 'placeholder' => 'Optional'], 'required' => false, 'empty_data' => '']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Player::class]);
    }
}
