<?php

namespace App\Controller;

use App\Entity\Emprunt;
use App\Form\EmpruntType;
use App\Repository\BookRepository;
use App\Repository\EmpruntRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/emprunt')]
final class EmpruntController extends AbstractController
{
    /**
     * 1. LISTE GLOBALE (ADMIN/EDITOR)
     */
    #[Route('', name: 'app_emprunt_index', methods: ['GET'])]
    #[IsGranted('ROLE_EDITOR')] 
    public function index(EmpruntRepository $empruntRepository): Response
    {
        return $this->render('emprunt/index.html.twig', [
            'emprunts' => $empruntRepository->findAll(),
        ]);
    }

    /**
     * 2. CRÉER UN NOUVEL EMPRUNT (USER)
     */
    #[Route('/new', name: 'app_emprunt_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, EntityManagerInterface $entityManager, BookRepository $bookRepository): Response
    {
        $emprunt = new Emprunt();
        $emprunt->setUser($this->getUser());

        $bookId = $request->query->get('book_id');
        if ($bookId) {
            $book = $bookRepository->find($bookId);
            if ($book && $book->getStock() <= 0) {
                $this->addFlash('danger', 'Désolé, le stock de "' . $book->getTitre() . '" est épuisé.');
                return $this->redirectToRoute('app_home');
            }
            if ($book) { $emprunt->setBook($book); }
        }

        $form = $this->createForm(EmpruntType::class, $emprunt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $book = $emprunt->getBook();
            $book->setStock($book->getStock() - 1);
            $emprunt->setStatus('En cours');
            $emprunt->setDateEmprunt(new \DateTimeImmutable());

            $entityManager->persist($emprunt);
            $entityManager->flush();

            $this->addFlash('success', 'Emprunt validé !');
            return $this->redirectToRoute('app_home');
        }

        return $this->render('emprunt/new.html.twig', [
            'emprunt' => $emprunt,
            'form' => $form,
        ]);
    }

    /**
     * 3. HISTORIQUE (EDITOR)
     * Placée avant les routes {id} pour éviter les conflits
     */
    #[Route('/historique', name: 'app_emprunt_history', methods: ['GET'])]
    #[IsGranted('ROLE_EDITOR')]
    public function history(EmpruntRepository $empruntRepository): Response
    {
        $emprunts = $empruntRepository->findBy([], [
            'user' => 'ASC',
            'dateEmprunt' => 'DESC'
        ]);

        $aujourdhui = new \DateTimeImmutable();

        return $this->render('emprunt/history.html.twig', [
            'emprunts' => $emprunts,
            'aujourdhui' => $aujourdhui,
        ]);
    }

    /**
     * 4. MES EMPRUNTS (USER)
     */
    #[Route('/mes-emprunts', name: 'app_emprunt_mes_emprunts', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function mesEmprunts(EmpruntRepository $empruntRepository): Response
    {
        $user = $this->getUser();
        $mesEmprunts = $empruntRepository->findBy(
            ['user' => $user],
            ['dateEmprunt' => 'DESC']
        );

        return $this->render('emprunt/mes_emprunts.html.twig', [
            'emprunts' => $mesEmprunts,
        ]);
    }

    /**
     * 5. RENDRE UN LIVRE
     * Note le <\d+> : l'ID doit être un nombre
     */
    #[Route('/{id<\d+>}/rendre', name: 'app_emprunt_rendre', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function rendre(Emprunt $emprunt, EntityManagerInterface $entityManager): Response
    {
        $emprunt->setStatus('Rendu');
        $book = $emprunt->getBook();
        
        if ($book) {
            $book->setStock($book->getStock() + 1);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Livre récupéré.');

        return $this->redirectToRoute('app_emprunt_index');
    }

    /**
     * 6. VOIR UN EMPRUNT
     */
    #[Route('/{id<\d+>}', name: 'app_emprunt_show', methods: ['GET'])]
    #[IsGranted('ROLE_EDITOR')]
    public function show(Emprunt $emprunt): Response
    {
        return $this->render('emprunt/show.html.twig', [
            'emprunt' => $emprunt,
        ]);
    }

    /**
     * 7. MODIFIER UN EMPRUNT
     */
    #[Route('/{id<\d+>}/edit', name: 'app_emprunt_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, Emprunt $emprunt, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EmpruntType::class, $emprunt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_emprunt_index');
        }

        return $this->render('emprunt/edit.html.twig', [
            'emprunt' => $emprunt,
            'form' => $form,
        ]);
    }

    /**
     * 8. SUPPRIMER UN EMPRUNT (POST)
     */
    #[Route('/{id<\d+>}', name: 'app_emprunt_delete', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function delete(Request $request, Emprunt $emprunt, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$emprunt->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($emprunt);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_emprunt_index');
    }
}