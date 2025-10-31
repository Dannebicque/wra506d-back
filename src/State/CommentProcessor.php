<?php
// src/State/CommentProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Comment;
use Symfony\Bundle\SecurityBundle\Security;

final class CommentProcessor implements ProcessorInterface
{
    public function __construct(private CurrentWorkspace $ctx, private Security $security) {}

    public function process($data, Operation $operation, array $uriUriVariables = [], array $context = [])
    {
        if (!$data instanceof Comment) return $data;

        $ws = $this->ctx->get();
        $data->setWorkspace($ws);

        if ($data->getPublication()->getWorkspace()->getId() !== $ws->getId()) {
            throw new \RuntimeException('Publication hors workspace');
        }
        if ($data->getParent() && $data->getParent()->getWorkspace()->getId() !== $ws->getId()) {
            throw new \RuntimeException('Parent comment hors workspace');
        }

        if ($this->security->getUser()) $data->setAuthor($this->security->getUser());
        return $data;
    }
}
