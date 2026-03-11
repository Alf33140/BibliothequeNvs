<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Stock;
use App\Form\BookType;
use App\Form\StockHistoryType;
use App\Repository\BookRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/book')]
final class BookController extends AbstractController
{
    /**
     * LISTE DES LIVRES
     */
    #[Route('/', name: 'app_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findAll(),
        ]);
    }

    /**
     * AJOUT D'UN NOUVEAU LIVRE
     */
    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $image */
            $image = $form->get('image')->getData();
            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME); 
                $safeImageName = $slugger->slug($originalName);
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();
                
                try { 
                    $image->move($this->getParameter('image_directory'), $newFileImageName);
                    $book->setImage($newFileImageName); 
                } catch (FileException $exception) {
                   
                }
            }
        
            $entityManager->persist($book);
            $entityManager->flush(); 
          
            // Création de l'entrée initiale en stock
            $stockHistory = new Stock(); 
            $stockHistory->setQuantity($book->getStock());
            $stockHistory->setLivre($book);
            $stockHistory->setCreatedAt(new \DateTimeImmutable()); 
    
            $entityManager->persist($stockHistory);
            $entityManager->flush();

            $this->addFlash('success', 'Votre livre a été ajouté');
            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    /**
     * AJOUT DE STOCK (Route précise avant le {id})
     */
    #[Route('/stock/add/{id}', name:'app_book_stock_add', methods: ['GET','POST'])]
    public function addStock(Request $request, EntityManagerInterface $entityManager, Book $book): Response 
    { 
        $stockAdd = new Stock();
        $form = $this->createForm(StockHistoryType::class, $stockAdd);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if($stockAdd->getQuantity() > 0){ 
                $newQuantity = $book->getStock() + $stockAdd->getQuantity(); 
                $book->setStock($newQuantity);
                $stockAdd->setCreatedAt(new \DateTimeImmutable());
                $stockAdd->setLivre($book);

                $entityManager->persist($stockAdd);
                $entityManager->flush();

                $this->addFlash('success','Le stock du produit a été modifié');
                return $this->redirectToRoute('app_book_index');
            } else {
                $this->addFlash('error','La quantité doit être supérieure à 0');
                return $this->redirectToRoute('app_book_stock_add', ['id' => $book->getId()]);
            }
        }

        return $this->render('book/addstock.html.twig', [
            'form' => $form->createView(), 
            'book' => $book, 
        ]);
    }

    /**
     * HISTORIQUE DU STOCK (Route précise avant le {id})
     */
    #[Route('/stock/history/{id}', name:'app_stock_add_history', methods: ['GET','POST'])]
    public function showHistoryBookStock(Book $book, StockRepository $stockRepository): Response
    {
        $stockHistory = $stockRepository->findBy(['livre' => $book], ['id' => 'DESC']);
        
        return $this->render('book/showHistory.html.twig', [
           'stockHistories' => $stockHistory,
           'book' => $book 
        ]);
    }

    /**
     * MODIFIER UN LIVRE
     */
    #[Route('/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $image = $form->get('image')->getData();
            if ($image) {
                $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeImageName = $slugger->slug($originalName);
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();

                try {
                    $image->move($this->getParameter('image_directory'), $newFileImageName);
                    $book->setImage($newFileImageName);
                } catch (FileException $exception) {}
            }
            $entityManager->flush();
            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    /**
     * SUPPRIMER UN LIVRE
     */
    #[Route('/admin/delete/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * VOIR UN LIVRE (DOIT ÊTRE EN DERNIER)
     */
    #[Route('/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
    

        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }
}