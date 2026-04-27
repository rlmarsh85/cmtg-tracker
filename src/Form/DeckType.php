<?php

namespace App\Form;

use App\Entity\Deck;
use App\Entity\Player;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeckType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Deck Name', 'attr' => ['class' => 'form-control']])
            ->add('format', ChoiceType::class, [
                'label' => 'Format',
                'choices' => array_combine(Deck::FORMATS, Deck::FORMATS),
                'attr' => ['class' => 'form-select'],
            ])
            ->add('colors', ChoiceType::class, [
                'label' => 'Colors',
                'choices' => ['White' => 'White', 'Blue' => 'Blue', 'Black' => 'Black', 'Red' => 'Red', 'Green' => 'Green'],
                'expanded' => true,
                'multiple' => true,
                'mapped' => false,
                'required' => false,
            ])
            ->add('commander', TextType::class, [
                'label' => 'Commander / General',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g. Atraxa, Praetors\' Voice'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 3],
            ])
            ->add('player', EntityType::class, [
                'class' => Player::class,
                'choice_label' => 'username',
                'label' => 'Owner',
                'attr' => ['class' => 'form-select'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Deck::class]);
    }
}
