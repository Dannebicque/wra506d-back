<?php
// src/State/PublicationProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Publication;
use Symfony\Bundle\SecurityBundle\Security;

final class PublicationProcessor implements ProcessorInterface
{
    public function __construct(private CurrentWorkspace $ctx, private Security $security) {}

    public function process($data, Operation $operation, array $uriVars = [], array $context = [])
    {
        if (!$data instanceof Publication) return $data;

        $ws = $this->ctx->get();
        $data->setWorkspace($ws);

        // sanity: channel doit être dans le même WS
        if ($data->getChannel()->getWorkspace()->getId() !== $ws->getId()) {
            throw new \RuntimeException('Channel hors workspace');
        }

        if ($this->security->getUser()) $data->setAuthor($this->security->getUser());
        return $data;
    }
}
