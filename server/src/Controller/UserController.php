<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class UserController extends AbstractController
{
    #[Route('/api/users/me', name: 'api_users_me', methods: ['GET'])]
    public function me(Security $security): JsonResponse
    {
        $user = $security->getUser();
        
        if (!$user) {
            return $this->json(null, 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(), // ici tu as les rôles, ex: ["ROLE_USER", "ROLE_ADMIN"]
            'isAdmin' => in_array('ROLE_ADMIN', $user->getRoles())
        ]);
    }
}
