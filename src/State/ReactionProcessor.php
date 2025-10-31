<?php
// src/State/ReactionProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Reaction;
use Symfony\Bundle\SecurityBundle\Security;

final class ReactionProcessor implements ProcessorInterface
{
    public function __construct(private CurrentWorkspace $ctx, private Security $security) {}

    public function process($data, Operation $op, array $uriVars = [], array $context = [])
    {
        if (!$data instanceof Reaction) return $data;

        $ws = $this->ctx->get();
        $data->setWorkspace($ws);

        $pub = $data->getPublication();
        $com = $data->getComment();

        if (($pub && $com) || (!$pub && !$com)) {
            throw new \RuntimeException('Cible invalide: préciser publication OU comment');
        }
        if ($pub && $pub->getWorkspace()->getId() !== $ws->getId()) {
            throw new \RuntimeException('Publication hors workspace');
        }
        if ($com && $com->getWorkspace()->getId() !== $ws->getId()) {
            throw new \RuntimeException('Comment hors workspace');
        }

        $user = $this->security->getUser();
        if ($user) $data->setUser($user);

        return $data;
    }
}
