<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Category; // Ne pas oublier cet import
use Symfony\Bridge\Doctrine\Form\Type\EntityType; // Ne pas oublier cet import
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('auteur')
            ->add('isbn')
            ->add('image', FileType::class, [
                'label'=> 'Image du Produit',
                'mapped' => false,
                'required'=> false,
                'constraints' => [
                    new File(
                        maxSize: '1024k',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                        ],
                        maxSizeMessage: 'La taille du fichier ne doit pas dépasser 1 Mo.',
                        mimeTypesMessage: 'Veuillez choisir un fichier de type image (JPEG, PNG, GIF)!!',
                    ),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'categorie', // Utilise le champ "categorie" de ton entité Category
                'label' => 'Catégorie du livre'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}