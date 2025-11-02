<?php
namespace App\Controller;

use App\Repository\WorkspaceRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class WorkspaceUserController extends AbstractController
{
    public function __construct(
        private WorkspaceRepository $workspaceRepository,
        private Security $security
    ) {}

    #[Route('/api/{slug}/users/me', name: 'api_workspace_user_me', methods: ['GET'])]
    public function me(string $slug): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $workspace = $this->workspaceRepository->findOneBy(['slug' => $slug]);
        if (!$workspace) {
            return new JsonResponse(['message' => 'Workspace not found'], Response::HTTP_NOT_FOUND);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'username' => $user->getDisplayName(),
            'roles' => $user->getRoles(),
            'avatar' => $user->getAvatar(),
            'workspace' => [
                'id' => $workspace->getId(),
                'slug' => $workspace->getSlug(),
            ],
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }
}
