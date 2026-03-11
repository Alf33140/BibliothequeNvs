<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    // On met '/' pour que ce soit la racine du site
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // On affiche ton nouveau fichier de présentation
        return $this->render('home/accueil.html.twig');
    }
}