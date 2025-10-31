<?php
// src/State/MediaProcessor.php
namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Context\CurrentWorkspace;
use App\Entity\Media;
use Symfony\Bundle\SecurityBundle\Security;

final class MediaProcessor implements ProcessorInterface
{
    public function __construct(private CurrentWorkspace $ctx, private Security $security) {}

    public function process($data, Operation $operation, array $uriUriVariables = [], array $context = [])
    {
        if (!$data instanceof Media) return $data;

        $ws = $this->ctx->get();
        $data->setWorkspace($ws);

        if ($this->security->getUser()) $data->setUploadedBy($this->security->getUser());

        return $data;
    }
}
