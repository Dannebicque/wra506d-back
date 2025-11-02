<?php
// src/Controller/AccountSetupController.php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\WorkspaceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/auth/setup')]
final class AccountSetupController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private UserRepository $users,
        private WorkspaceRepository $workspaces,
        private UserPasswordHasherInterface $hasher
    ) {}

    /**
     * Étape 1 : l’étudiant demande un mail avec le lien de setup + slug + code.
     */
    #[Route('/request', name: 'auth_setup_request', methods: ['POST'])]
    public function requestSetup(Request $request, ValidatorInterface $validator): Response
    {
        $payload = json_decode($request->getContent() ?: '{}', true);
        $email = (string)($payload['email'] ?? '');

        $viol = $validator->validate($email, [new Assert\NotBlank(), new Assert\Email()]);
        if (count($viol) > 0) {
            return $this->json(['error' => 'Email invalide'], Response::HTTP_BAD_REQUEST);
        }

        /** @var User|null $user */
        $user = $this->users->findOneBy(['email' => strtolower($email)]);
        if (!$user) {
            // Pour éviter de “leaker” les emails, on répond OK quand même
            return $this->json(['status' => 'ok']);
        }

        $ws = $user->getWorkspace();
        // Régénère un join code frais (en clair pour le mail, hash en base)
        $joinCodePlain = bin2hex(random_bytes(4));
        $ws->setJoinCodeHash(password_hash($joinCodePlain, PASSWORD_BCRYPT));

        // Génère un setup token (24h)
        $token = bin2hex(random_bytes(20));
        $user->setSetupToken($token);
        $user->setSetupTokenExpiresAt((new \DateTimeImmutable())->modify('+24 hours'));

        $this->em->flush();

        // Compose le mail
        $link = $request->getSchemeAndHttpHost().'/auth/setup/'.$token;
        $body = sprintf(
            "Bonjour,\n\nVoici vos informations de configuration de compte :\n".
            "- Email : %s\n- Workspace (slug) : %s\n- Code d’ajout (join code) : %s\n\n".
            "Pour définir votre mot de passe, cliquez sur ce lien (valide 24h) :\n%s\n\n".
            "À bientôt.",
            $user->getEmail(),
            $ws->getSlug(),
            $joinCodePlain,
            $link
        );

        $emailMsg = (new Email())
            ->to($user->getEmail())
            ->subject('Configuration de votre compte (TP)')
            ->text($body);

        $this->mailer->send($emailMsg);

        return $this->json(['status' => 'ok']);
    }

    /**
     * Étape 2 : l’étudiant définit son mot de passe via le token reçu par mail.
     */
    #[Route('/{token}', name: 'auth_setup_apply', methods: ['POST'])]
    public function applySetup(string $token, Request $request, ValidatorInterface $validator): Response
    {
        /** @var User|null $user */
        $user = $this->users->findOneBy(['setupToken' => $token]);
        if (!$user) {
            return $this->json(['error' => 'Token invalide'], Response::HTTP_BAD_REQUEST);
        }

        $now = new \DateTimeImmutable();
        if (null === $user->getSetupTokenExpiresAt() || $user->getSetupTokenExpiresAt() < $now) {
            return $this->json(['error' => 'Token expiré'], Response::HTTP_BAD_REQUEST);
        }

        $payload = json_decode($request->getContent() ?: '{}', true);
        $password = (string)($payload['password'] ?? '');
        $viol = $validator->validate($password, [
            new Assert\NotBlank(),
            new Assert\Length(min: 8),
        ]);
        if (count($viol) > 0) {
            return $this->json(['error' => 'Mot de passe trop court (min 8)'], Response::HTTP_BAD_REQUEST);
        }

        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setSetupToken(null);
        $user->setSetupTokenExpiresAt(null);
        $this->em->flush();

        return $this->json(['status' => 'password_set']);
    }
}
