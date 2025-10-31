<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Workspace;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class StudentWorkspacesFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $om): void
    {
        $letters = range('a', 'z');
        $rows = [];
        foreach ($letters as $letter) {
            $slug = sprintf('ws-%s', $letter);
            $email = sprintf('etudiant.%s@tp.local', $letter);

            // code d’inscription aléatoire (affiché une seule fois)
            $joinCodePlain = bin2hex(random_bytes(4)); // 8 hex chars
            $joinCodeHash  = password_hash($joinCodePlain, PASSWORD_BCRYPT);

            $ws = new Workspace();
            $ws->setSlug($slug);
            $ws->setAllowSelfSignup(true);
            $ws->setJoinCodeHash($joinCodeHash);
            $om->persist($ws);

            $user = new User();
            $user->setEmail($email);
            $user->setDisplayName($email);
            $user->setAvatar(null);
            $user->setRoles(['ROLE_OWNER']);
            $user->setWorkspace($ws);

            // mot de passe temporaire (random) — l’étudiant le remplacera via le setup token
            $tempPassword = bin2hex(random_bytes(6));
            $user->setPassword($this->hasher->hashPassword($user, $tempPassword));

            // token de setup initial (valide 7 jours)
            $token = bin2hex(random_bytes(20));
            $user->setSetupToken($token);
            $user->setSetupTokenExpiresAt((new \DateTimeImmutable())->modify('+7 days'));

            $om->persist($user);

            $rows[] = [
                'email'        => $email,
                'slug'         => $slug,
                'join_code'    => $joinCodePlain,
                'setup_token'  => $token,
                'temp_password'=> $tempPassword,
            ];
        }

        $om->flush();

        // Sauvegarde un CSV pour le prof (utile pour diffuser aux étudiants)
        $dir = \dirname(__DIR__, 2).'/var/fixtures';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $file = $dir.'/workspaces.csv';
        $fp = fopen($file, 'w');
        fputcsv($fp, ['email','slug','join_code','setup_token','temp_password']);
        foreach ($rows as $r) { fputcsv($fp, $r); }
        fclose($fp);

        // Affiche le chemin dans la console
        echo PHP_EOL.'[Fixtures] CSV généré: '.$file.PHP_EOL;
    }
}
