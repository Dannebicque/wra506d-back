<?php
// src/State/CommentProcessor.php
namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Channel;
use App\Entity\Comment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class CommentProcessor implements ProcessorInterface
{
    public function __construct(
        private PersistProcessor $persistProcessor,
        private CurrentWorkspace $currentWorkspace,
        private EntityManagerInterface $em,
        private Security $security) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Comment) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        };

        $isPost = strtoupper($operation->getMethod() ?? 'POST') === 'POST';

        if ($isPost) {
            // ⚠️ Créer une nouvelle entité pour garantir un INSERT
            $new = new Comment();
            $new->setBody($data->getBody());
            $new->setPublication($data->getPublication());

            // Workspace depuis {slug}
            if ($ws = $this->currentWorkspace->get()) {
                $new->setWorkspace($ws);
            }

            if (($u = $this->security->getUser()) && method_exists($new, 'setAuthor')) {
                $new->setAuthor($u);
            }

            $now = new \DateTimeImmutable();
            $new->setCreatedAt($now);
            $new->setUpdatedAt($now);

            // Détacher l’eventuel $data déjà managé (par précaution)
            if ($this->em->contains($data)) {
                $this->em->detach($data);
            }

            return $this->persistProcessor->process($new, $operation, $uriVariables, $context);
        }

        // PATCH (ou autre write): compléter proprement sans changer l’identité
        if (($ws = $this->currentWorkspace->get()) && $data->getWorkspace() !== $ws) {
            $data->setWorkspace($ws);
        }

        if (($u = $this->security->getUser()) && method_exists($data, 'setAuthor') && null === $data->getAuthor()) {
            $data->setAuthor($u);
        }

        $data->setUpdatedAt(new \DateTimeImmutable());

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
