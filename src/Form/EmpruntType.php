<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Emprunt;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmpruntType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('dateEmprunt', null, [
            'widget' => 'single_text',
        ])
        ->add('dateRetour', null, [
            'widget' => 'single_text',
            'required' => false,
        ])
        // SURTOUT PAS DE CHAMP STATUS ICI
        ->add('book', EntityType::class, [
            'class' => Book::class,
            'choice_label' => 'titre',
        ])
        ->add('user', EntityType::class, [
            'class' => User::class,
            'choice_label' => 'lastname',
        ])
    ;
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Emprunt::class,
        ]);
    }
}