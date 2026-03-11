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
     * SEULS L'ADMIN ET L'EDITOR PEUVENT VOIR LA LISTE
     */
    #[Route(name: 'app_emprunt_index', methods: ['GET'])]
    #[IsGranted('ROLE_EDITOR')] 
    public function index(EmpruntRepository $empruntRepository): Response
    {
        return $this->render('emprunt/index.html.twig', [
            'emprunts' => $empruntRepository->findAll(),
        ]);
    }

    /**
     * TOUT LE MONDE (USER, EDITOR, ADMIN) PEUT ACCÉDER À CETTE ROUTE
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
        
        // --- VÉRIFICATION SÉCURITÉ ---
        if ($book && $book->getStock() <= 0) {
            $this->addFlash('danger', 'Désolé, le stock de "' . $book->getTitre() . '" est épuisé.');
            return $this->redirectToRoute('app_home');
        }
        
        if ($book) { $emprunt->setBook($book); }
    }

    $form = $this->createForm(EmpruntType::class, $emprunt);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $book = $emprunt->getBook(); // Récupère l'objet Book lié via book_id

        // On baisse le stock de 1
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
#[Route('/{id}/rendre', name: 'app_emprunt_rendre', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_EDITOR')]
public function rendre(Emprunt $emprunt, EntityManagerInterface $entityManager): Response
{
    // On passe le statut à rendu
    $emprunt->setStatus('Rendu');
    
    // On récupère l'entité Book liée à cet emprunt
    $book = $emprunt->getBook();
    
    if ($book) {
        // On remet 1 exemplaire en stock
        $book->setStock($book->getStock() + 1);
    }

    $entityManager->flush();

    $this->addFlash('success', 'Livre récupéré. Le stock est de nouveau à ' . $book->getStock());

    return $this->redirectToRoute('app_emprunt_index');
}
    /**
     * HISTORIQUE DES EMPRUNTS AVEC CALCUL DU RETARD
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
            'aujourdhui' => $aujourdhui, // On envoie la date du jour à Twig
        ]);
    }

    #[Route('/mes-emprunts', name: 'app_emprunt_mes_emprunts', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
public function mesEmprunts(EmpruntRepository $empruntRepository): Response
{
    // On récupère l'utilisateur actuellement connecté
    $user = $this->getUser();

    // On cherche tous les emprunts liés à cet utilisateur, triés par date récente
    $mesEmprunts = $empruntRepository->findBy(
        ['user' => $user],
        ['dateEmprunt' => 'DESC']
    );

    return $this->render('emprunt/mes_emprunts.html.twig', [
        'emprunts' => $mesEmprunts,
    ]);
}

    /**
     * SHOW, EDIT ET DELETE : RESERVÉS À L'EDITOR ET L'ADMIN
     */
    #[Route('/{id}', name: 'app_emprunt_show', methods: ['GET'])]
    #[IsGranted('ROLE_EDITOR')]
    public function show(Emprunt $emprunt): Response
    {
        return $this->render('emprunt/show.html.twig', [
            'emprunt' => $emprunt,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_emprunt_edit', methods: ['GET', 'POST'])]
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

    #[Route('/{id}', name: 'app_emprunt_delete', methods: ['POST'])]
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