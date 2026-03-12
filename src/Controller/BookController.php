<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Stock;
use App\Form\BookType;
use App\Form\StockHistoryType;
use App\Repository\BookRepository;
use App\Repository\CategoryRepository;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

final class BookController extends AbstractController
{
    // 1. LA PAGE D'ACCUEIL (Bienvenue) - Route fixe en premier
    #[Route('/', name: 'app_home')]
    public function accueil(): Response
    {
        return $this->render('home/accueil.html.twig');
    }

    // 2. LE CATALOGUE - Route fixe
    #[Route('/catalogue', name: 'app_book_index')] 
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'categories' => $categoryRepository->findAll(),
        ]);
    }

    // 3. ADMIN GESTION - Route fixe
    #[Route('/admin/gestion', name: 'app_book_admin_index', methods: ['GET'])] 
    public function adminIndex(BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findAll(),
        ]);
    }

    // 4. NOUVEAU LIVRE - Route fixe
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
                } catch (FileException $exception) {}
            }
        
            $entityManager->persist($book);
            $entityManager->flush(); 
          
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

    // 5. AJOUT DE STOCK (Contrainte ID nombre)
    #[Route('/stock/add/{id<\d+>}', name:'app_book_stock_add', methods: ['GET','POST'])]
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

        return $this->render('emprunt/addstock.html.twig', [
            'form' => $form->createView(), 
            'book' => $book, 
        ]);
    }

    // 6. HISTORIQUE STOCK (Contrainte ID nombre)
    #[Route('/stock/history/{id<\d+>}', name:'app_stock_add_history', methods: ['GET','POST'])]
    public function showHistoryBookStock(Book $book, StockRepository $stockRepository): Response
    {
        $stockHistory = $stockRepository->findBy(['livre' => $book], ['id' => 'DESC']);
        
        return $this->render('book/showHistory.html.twig', [
           'stockHistories' => $stockHistory,
           'book' => $book 
        ]);
    }

    // 7. MODIFIER UN LIVRE (Contrainte ID nombre)
    #[Route('/{id<\d+>}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
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
     * 9. VOIR UN LIVRE (En dernier, avec contrainte nombre)
     */
    #[Route('/{id<\d+>}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book, 
        ]);
    }
}