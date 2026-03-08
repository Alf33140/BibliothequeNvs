<?php

namespace App\Controller;

use App\Entity\User;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user_index')]
    public function index(UserRepository $userRepository): Response  
    {
        $users = $userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }
    
    #[Route('/admin/user/{id}/change-role/{role}', name: 'app_user_change_role')] //remettre la wildcard {role} pour selection du role en question
    public function changeRole(EntityManagerInterface $entityManager, User $user, $role): Response
    {

    $validRoles = ['ROLE_EDITOR', 'ROLE_USER'];
    if (!in_array($role, $validRoles, true)) {
            $this->addFlash('error', "Le rôle demandé n'est pas valide.");
            return $this->redirectToRoute('app_user_index');
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
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/admin/user/{id}/delete/editor/role ', name: 'app_user_delete_editor_role')]
    public function removeRoleEditor(EntityManagerInterface $entityManager, User $user): Response
    {
        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('danger', "Le rôle éditeur à bien été retiré à l'utilisateur");
        
        return $this->redirectToRoute('app_user_index');
    }
 #[Route('/admin/user/{id}/delete/', name: 'app_user_delete')]
    public function ruserRemove(EntityManagerInterface $entityManager,$id,  UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', "L'utilisateur à bien été supprimé.");
        
        return $this->redirectToRoute('app_user_index');
    }

}