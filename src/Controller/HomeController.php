<?php

namespace App\Controller;
use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(BookRepository $bookRepository): Response
    {
        $books = $bookRepository->findAll();
    
        return $this->render('home/index.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/book', name: 'app_book_show', methods: ['GET', 'POST'])]
    public function showProductHomepage(): Response
    {
        return $this->render('home/index.html.twig');
    }
        
            
  
}
