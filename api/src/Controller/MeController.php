<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class MeController extends AbstractController
{
    /**
     * Retourne l'utilisateur authentifié.
     *
     * Le JWT étant transporté par un cookie httpOnly, le front ne peut pas le
     * lire : cet endpoint est son seul moyen de connaître l'état de session.
     */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?User $user): JsonResponse
    {
        // L'access_control (^/api => ROLE_USER) rejette déjà les anonymes ; cette
        // garde protège d'un assouplissement futur de la configuration.
        if (null === $user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        // Champs listés explicitement pour exclure le mot de passe hashé.
        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }
}
