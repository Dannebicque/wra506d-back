<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ApiRegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface      $em,
        private UserRepository              $users,
        private WorkspaceRepository         $workspaces,
        private UserPasswordHasherInterface $hasher,
        private ValidatorInterface          $validator
    )
    {
    }

    #[Route('/api/{slug}/register', name: 'api_register_slug', methods: ['POST'])]
    public function registerWithSlug(
        WorkspaceRepository $workspaceRepository,
        Request $request, string $slug): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true);

        $displayName = (string)($payload['displayName'] ?? '');
        $email = (string)($payload['email'] ?? '');
        $password = (string)($payload['password'] ?? '');
        $code = (string)($payload['codeInscription'] ?? '');

        // Validation basique
        $viol = $this->validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);
        if (count($viol) > 0) {
            return $this->json(['error' => 'Email invalide'], Response::HTTP_BAD_REQUEST);
        }
        $viol = $this->validator->validate($password, [new Assert\NotBlank(), new Assert\Length(min: 8)]);
        if (count($viol) > 0) {
            return $this->json(['error' => 'Mot de passe trop court (min 8)'], Response::HTTP_BAD_REQUEST);
        }
        $viol = $this->validator->validate($displayName, [new Assert\NotBlank()]);
        if (count($viol) > 0) {
            return $this->json(['error' => 'displayName requis'], Response::HTTP_BAD_REQUEST);
        }
        if ($code === '') {
            return $this->json(['error' => 'codeInscription requis'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifie si l'utilisateur existe déjà
        if ($this->users->findOneBy(['email' => strtolower($email)])) {
            return $this->json(['error' => 'Email déjà utilisé'], Response::HTTP_CONFLICT);
        }

        $foundWorkspace = $workspaceRepository->findOneBy(['slug' => $slug]);

        if (!$foundWorkspace) {
            return $this->json(['error' => 'Workspace inexistant'], Response::HTTP_BAD_REQUEST);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail(strtolower($email));
        $user->setDisplayName($displayName);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setWorkspace($foundWorkspace);

        $this->em->persist($user);
        $this->em->flush();

        $response = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'workspace' => [
                'id' => $user->getWorkspace()?->getId(),
                'slug' => $user->getWorkspace()?->getSlug(),
            ],
        ];

        return $this->json($response, Response::HTTP_CREATED);
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true);

        $displayName = (string)($payload['displayName'] ?? '');
        $email = (string)($payload['email'] ?? '');
        $password = (string)($payload['password'] ?? '');
        $code = (string)($payload['codeInscription'] ?? '');

        // Validation basique
        $viol = $this->validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);
        if (count($viol) > 0) {
            return $this->json(['error' => 'Email invalide'], Response::HTTP_BAD_REQUEST);
        }
        $viol = $this->validator->validate($password, [new Assert\NotBlank(), new Assert\Length(min: 8)]);
        if (count($viol) > 0) {
            return $this->json(['error' => 'Mot de passe trop court (min 8)'], Response::HTTP_BAD_REQUEST);
        }
        $viol = $this->validator->validate($displayName, [new Assert\NotBlank()]);
        if (count($viol) > 0) {
            return $this->json(['error' => 'displayName requis'], Response::HTTP_BAD_REQUEST);
        }
        if ($code === '') {
            return $this->json(['error' => 'codeInscription requis'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifie si l'utilisateur existe déjà
        if ($this->users->findOneBy(['email' => strtolower($email)])) {
            return $this->json(['error' => 'Email déjà utilisé'], Response::HTTP_CONFLICT);
        }

        // Recherche du workspace correspondant au code (vérification du hash)
        $foundWorkspace = null;
        foreach ($this->workspaces->findAll() as $ws) {
            if (!method_exists($ws, 'getJoinCodeHash')) {
                continue;
            }
            $hash = $ws->getJoinCodeHash();
            if ($hash && password_verify($code, $hash)) {
                $foundWorkspace = $ws;
                break;
            }
        }

        if (!$foundWorkspace) {
            return $this->json(['error' => 'Code d\'inscription invalide'], Response::HTTP_BAD_REQUEST);
        }

        // Création de l'utilisateur
        $user = new User();
        $user->setEmail(strtolower($email));
        $user->setDisplayName($displayName);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setWorkspace($foundWorkspace);

        $this->em->persist($user);
        $this->em->flush();

        $response = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'displayName' => $user->getDisplayName(),
            'workspace' => [
                'id' => $user->getWorkspace()?->getId(),
                'slug' => $user->getWorkspace()?->getSlug(),
            ],
        ];

        return $this->json($response, Response::HTTP_CREATED);
    }
}
