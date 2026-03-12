<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        // Recupde toutes les catégories 
        $categories = $categoryRepository->findAll();

        return $this->render('home/accueil.html.twig', [
            'categories' => $categories, 
        ]);
    }
}