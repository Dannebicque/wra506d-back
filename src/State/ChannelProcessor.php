<?php
// src/State/ChannelProcessor.php
namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Channel;
use App\Repository\ChannelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

final class ChannelProcessor implements ProcessorInterface
{
    public function __construct(
        private PersistProcessor $persistProcessor,
        private CurrentWorkspace $currentWorkspace,
        private ChannelRepository $channels,
        private EntityManagerInterface $em,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Channel) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $isPost = strtoupper($operation->getMethod() ?? 'POST') === 'POST';

        if ($isPost) {
            // ⚠️ Créer une nouvelle entité pour garantir un INSERT
            $new = new Channel();
            $new->setName($data->getName());

            // Workspace depuis {slug}
            if ($ws = $this->currentWorkspace->get()) {
                $new->setWorkspace($ws);
            }

            // Slug auto si manquant
            if (!$data->getSlug() || trim((string)$data->getSlug()) === '') {
                $new->setSlug($this->generateUniqueSlug($new));
            } else {
                $new->setSlug($data->getSlug());
            }

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

        // Ne pas régénérer le slug si déjà défini (comportement conservateur)
        if (!$data->getSlug() || trim((string)$data->getSlug()) === '') {
            $data->setSlug($this->generateUniqueSlug($data));
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    /**
     * Génère un slug unique par workspace à partir du name (ou 'channel').
     */
    private function generateUniqueSlug(Channel $channel): string
    {
        $slugger = new AsciiSlugger();
        $base = $slugger->slug((string)($channel->getName() ?: 'channel'))->lower()->toString();

        $ws = $channel->getWorkspace();
        $slug = $base;
        $i = 2;

        while ((int)$this->channels->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->andWhere('c.workspace = :ws')->setParameter('ws', $ws)
                ->andWhere('c.slug = :slug')->setParameter('slug', $slug)
                ->getQuery()->getSingleScalarResult() > 0
        ) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
