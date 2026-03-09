<?php

namespace App\Controller;

use App\Entity\StockLivre;
use App\Form\StockLivreType;
use App\Repository\StockLivreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stock/livre')]
final class StockLivreController extends AbstractController
{
    #[Route(name: 'app_stock_livre_index', methods: ['GET'])]
    public function index(StockLivreRepository $stockLivreRepository): Response
    {
        return $this->render('stock_livre/index.html.twig', [
            'stock_livres' => $stockLivreRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_stock_livre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stockLivre = new StockLivre();
        $form = $this->createForm(StockLivreType::class, $stockLivre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stockLivre);
            $entityManager->flush();

            return $this->redirectToRoute('app_stock_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stock_livre/new.html.twig', [
            'stock_livre' => $stockLivre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_livre_show', methods: ['GET'])]
    public function show(StockLivre $stockLivre): Response
    {
        return $this->render('stock_livre/show.html.twig', [
            'stock_livre' => $stockLivre,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_livre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, StockLivre $stockLivre, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StockLivreType::class, $stockLivre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_stock_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('stock_livre/edit.html.twig', [
            'stock_livre' => $stockLivre,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_livre_delete', methods: ['POST'])]
    public function delete(Request $request, StockLivre $stockLivre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stockLivre->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($stockLivre);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_stock_livre_index', [], Response::HTTP_SEE_OTHER);
    }
}
