<?php


namespace App\Controller;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class AuthTestController extends AbstractController
{
    #[Route('/', name: 'auth_test_login', methods: ['GET', 'POST'])]
    public function __invoke(
        Request                     $request,
        UserRepository              $users,
        UserPasswordHasherInterface $hasher,
        JWTTokenManagerInterface    $jwtManager,
    ): Response
    {
        $error = null;
        $token = null;
        $user = null;
        $ws = null;
        $email = '';
        if ($request->isMethod('POST')) {
            $email = strtolower(trim((string)$request->request->get('email')));
            $password = (string)$request->request->get('password');

            $user = $users->findOneBy(['email' => $email]);
            if (!$user) {
                $error = "Identifiants invalides.";
            } else {
                if (!$hasher->isPasswordValid($user, $password)) {
                    $error = "Identifiants invalides.";
                } else {
                    // OK → on génère un JWT
                    $token = $jwtManager->create($user);
                    $ws = $user->getWorkspace();
                }
            }
        }

        return $this->render('auth/test_login.html.twig', [
            'error' => $error,
            'token' => $token,
            'email' => $email,
            'ws' => $ws,
            'user' => $user,
        ]);
    }
}
