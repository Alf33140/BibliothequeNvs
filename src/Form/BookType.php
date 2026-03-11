<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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
            // ->add('description') // Mis en commentaire pour éviter l'erreur PropertyNotFound
            ->add('isbn')
            ->add('stock')
            ->add('image', FileType::class, [
                'label'=> 'Image du Livre',
                'mapped' => false,
                'required'=> false,
                'constraints' => [
                    new File(
                        maxSize: '1024k',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/gif',
                        ],
                        maxSizeMessage: 'La taille du fichier ne doit pas dépasser 1 Mo.',
                        mimeTypesMessage: 'Veuillez choisir un fichier de type image (JPEG, PNG,JPG, GIF)!!',
                    ),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                // On utilise 'Categorie' avec la majuscule pour correspondre à ton entité
                'choice_label' => 'Categorie', 
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