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
        // On récupère toutes les catégories (qui contiennent leurs livres grâce à la relation)
        $categories = $categoryRepository->findAll();

        return $this->render('home/accueil.html.twig', [
            'categories' => $categories, // On envoie la variable attendue par Twig
        ]);
    }
}