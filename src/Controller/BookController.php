<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/book')]
final class BookController extends AbstractController
{
    #[Route(name: 'app_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME); 
                $safeImageName = $slugger->slug($originalName);
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();
                
                try { 
                    $image->move
                        ($this->getParameter('image_directory'), 
                        $newFileImageName);
                }catch (FileException $exception) {}
                    $book->setImage($newFileImageName); 
                
            }
            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $image = $form->get('image')->getData();/* on recup l'image et son contenu*/
   
            if ($image) {/*si l'image existe*/
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeImageName = $slugger->slug($originalName);/* permet de recup des image avec espace dans le nom et l'enlever*/
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();/*cree un id unique a toute les images meme si elles ont un nom similaire*/

                try {
                    $image->move
                        ($this->getParameter('image_directory'),
                        $newFileImageName);/* on recup l'image et on la renomme et on la stocke dans le repoertoire */
                }catch (FileException $exception) {}/*en cas d'erreur*/
                    $book->setImage($newFileImageName);
                
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);

            return $this->render('book/edit.html.twig', [#on rend la vue du formulaire de modification du produit. On passe à la vue l'entité du produit à modifier et le formulaire lui-même pour qu'il puisse être affiché dans la page.
            'book' => $book,#on passe à la vue l'entité du produit à modifier pour qu'elle puisse être utilisée dans le template, par exemple pour pré-remplir les champs du formulaire avec les données actuelles du produit.
            'form' => $form,#on passe à la vue le formulaire lui-même pour qu'il puisse être affiché dans la page. Le formulaire contiendra les champs nécessaires pour modifier les informations du produit, et il sera lié à l'entité du produit pour que les données saisies soient automatiquement mappées à l'entité lors de la soumission du formulaire.
        ]);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }
}
