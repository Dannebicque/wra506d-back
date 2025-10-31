<?php
// src/State/PublicationProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Publication;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionProperty;
use Symfony\Bundle\SecurityBundle\Security;

final class PublicationProcessor implements ProcessorInterface
{
    public function __construct(
        private PersistProcessor $persist,
        private CurrentWorkspace $currentWorkspace,
        private Security $security,
        private EntityManagerInterface $em,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Publication) {
            return $this->persist->process($data, $operation, $uriVariables, $context);
        }

        $isPost = strtoupper($operation->getMethod() ?? 'POST') === 'POST';

        if ($isPost) {
            // ⚠️ Ne pas réutiliser $data : crée un nouvel objet pour garantir un INSERT
            $new = new Publication();
            $new->setTitle($data->getTitle());
            $new->setBody($data->getBody());
            $new->setChannel($data->getChannel());

            if ($ws = $this->currentWorkspace->get()) {
                $new->setWorkspace($ws);
            }
            if (($u = $this->security->getUser()) && method_exists($new, 'setAuthor')) {
                $new->setAuthor($u);
            }

            $now = new \DateTimeImmutable();
            $new->setCreatedAt($now);
            $new->setUpdatedAt($now);

            // Optionnel: détacher l'ancien $data si jamais il a été géré
            if ($this->em->contains($data)) {
                $this->em->detach($data);
            }

            return $this->persist->process($new, $operation, $uriVariables, $context);
        }

        // PATCH/PUT: on met juste à jour les dates + workspace si besoin
        if ($ws = $this->currentWorkspace->get()) {
            $data->setWorkspace($ws);
        }
        if (($u = $this->security->getUser()) && method_exists($data, 'setAuthor') && null === $data->getAuthor()) {
            $data->setAuthor($u);
        }
        $data->setUpdatedAt(new \DateTimeImmutable());

        return $this->persist->process($data, $operation, $uriVariables, $context);
    }

//    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
//    {
//        if (!$data instanceof Publication) {
//            return $this->persist->process($data, $operation, $uriVariables, $context);
//        }
//
//        // === GARDE-FOU : si c'est un POST, on force un INSERT ===
//        if (strtoupper($operation->getMethod() ?? 'POST') === 'POST') {
//            $data->setId(null);
//        }
//        // =========================================================
//
//        //dump(['before',$data->getId(), 'op'=>$operation->getName()]);
//        // workspace depuis {slug}
//        if ($ws = $this->currentWorkspace->get()) {
//            $data->setWorkspace($ws);
//        }
//
//        // auteur si besoin
//        if (method_exists($data, 'setAuthor') && ($u = $this->security->getUser())) {
//            $data->setAuthor($u);
//        }
//
//        $now = new \DateTimeImmutable();
//        if (null === $data->getCreatedAt()) {
//            $data->setCreatedAt($now);
//        }
//        $data->setUpdatedAt($now);
//
//        // surtout: ne pas charger/remplacer $data par un existant, ne pas toucher à l'id
//        return $this->persist->process($data, $operation, $uriVariables, $context);
//    }
}

#
