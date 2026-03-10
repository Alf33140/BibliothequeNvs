<?php

namespace App\Controller;

 
use App\Entity\Book;
use App\Entity\Stock; // Import de la classe de l'entité Stock pour gerer l'historique
use App\Form\BookType;
use App\Repository\BookRepository;
use App\Repository\StockRepository;
use App\Form\StockHistoryType;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Id;
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
        /** @var UploadedFile $image */
        $image = $form->get('image')->getData();
        if ($image) {
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME); 
            $safeImageName = $slugger->slug($originalName);
            $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();
            
            try { 
                $image->move(
                    $this->getParameter('image_directory'), 
                    $newFileImageName
                );
                $book->setImage($newFileImageName); 
            } catch (FileException $exception) { // Gestion de l'erreur
                
            }
        }
    
        $entityManager->persist($book);
        $entityManager->flush(); 
      
        
            $stockHistory = new Stock(); 
            $stockHistory->setQuantity($book->getStock());
            $stockHistory->setLivre($book);
            $stockHistory->setCreatedAt(new \DateTimeImmutable()); 
    
            $entityManager->persist($stockHistory);
            $entityManager->flush();

        

        $this->addFlash('success', 'Votre livre a été ajouté'); // Message Flash pour confirmer l'jout du livre"
        
        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('book/new.html.twig', [
        'book' => $book,
        'form' => $form,
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
                $safeImageName = $slugger->slug($originalName);/* permet de recup des images avec espace dans le nom et le concatener*/
                $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension();/*cree un id unique a toute les images meme si elles ont un nom similaire*/

                try {
                    $image->move
                        ($this->getParameter('image_directory'),
                        $newFileImageName);/* on recup l'image, on la renomme et on la stocke dans le repoertoire */
                }catch (FileException $exception) {}/*en cas d'erreur*/
                    $book->setImage($newFileImageName);
                
            }
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);

            
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }
    #region Show
   #[Route('/{id}', name: 'app_book_show', methods: ['GET', 'POST'])]
    public function show( Book $book): Response
    {
    

        // Vérifie bien si ton dossier est 'book' ou 'product'
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }
     #[Route('/add/book/{id}/', name:'app_book_stock_add', methods: ['GET','POST'])]
    public function addStock(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository, $id): Response 
    { 
        $book = $bookRepository->find($id);
        if (!$book) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        $stockAdd = new Stock();
        $form = $this->createForm(StockHistoryType::class, $stockAdd);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            if($stockAdd->getQuantity() > 0){ 
                $newQuantity = $book->getStock() + $stockAdd->getQuantity(); 
                $book->setStock($newQuantity);
                $stockAdd->setCreatedAt(new \DateTimeImmutable());
                $stockAdd->setLivre($book); // Utilisation du champ 'livre'

                $entityManager->persist($stockAdd);
                $entityManager->flush();

                $this->addFlash('success','Le stock du produit a été modifié');
                return $this->redirectToRoute('app_book_index');
            } else {
                $this->addFlash('error','La quantité doit être supérieure à 0'); // Suppression du 'e' parasite
                return $this->redirectToRoute('app_book_stock', ['id' => $book->getId()]);
            }
        }

        return $this->render('book/addstock.html.twig', [
            'form' => $form->createView(), 
            'book' => $book, 
        ]);
    }

    #[Route('/add/book/{id}/stock/history', name:'app_stock_add_history', methods: ['GET','POST'])]
    public function showHistoryBookStock($id, BookRepository $bookRepository, StockRepository $stockRepository): Response
    {
        $book = $bookRepository->find($id);
        
        // CORRECTION ICI : 'livre' au lieu de 'book'
        $stockHistory = $stockRepository->findBy(['livre' => $book], ['id' => 'DESC']);
        
        return $this->render('book/showHistory.html.twig', [ // Chemin harmonisé en 'book/'
           'stockHistories' => $stockHistory,
           'book' => $book // Utile pour afficher le titre du livre dans le template
        ]);
    }
}
