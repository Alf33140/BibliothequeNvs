<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('admin/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }

     #[Route('/admin/user/{id}/change-role/{role}', name: 'app_user_change_role')] //remettre la wildcard {role} pour selection du role en question
    public function changeRole(EntityManagerInterface $entityManager, User $user, $role): Response
    {

    $validRoles = ['ROLE_EDITOR', 'ROLE_USER'];
    if (!in_array($role, $validRoles, true)) {
            $this->addFlash('error', "Le rôle demandé n'est pas valide.");
            return $this->redirectToRoute('app_user');
        }
     if ($role !== 'ROLE_USER') {
            $user->setRoles([$role, 'ROLE_USER']);
        } else {
            $user->setRoles([$role]);
        }
        // Sauvegarde en base de données
        $entityManager->flush();
        // Message de confirmation
        $this->addFlash('success', "Le rôle a bien été attribué à l'utilisateur.");
        // Redirection vers la liste des utilisateurs
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/{id}/remove/editor/role ', name: 'app_user_remove_editor_role')]
    public function removeRoleEditor(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('danger', "Le rôle éditeur à bien été retiré à l'utilisateur");
        
        return $this->redirectToRoute('app_user');
    }

    #[Route('admin/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('admin/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/adlin/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/admin/{id}', name: 'app_user_delete', methods: [ 'GET', 'POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
