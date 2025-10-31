<?php
// src/Api/IriConverter.php
namespace App\Api;

use ApiPlatform\Metadata\IriConverterInterface; // v4 namespace
use ApiPlatform\Metadata\Operation;
use App\Context\CurrentWorkspace;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class IriConverter implements IriConverterInterface
{
    public function __construct(
        private IriConverterInterface $decorated,
        private CurrentWorkspace $currentWorkspace,
        private LoggerInterface $logger,
    ) {}

    private function ensureSlug(array &$context = [], array &$uriVariables = []): void
    {
        if (!isset($context['uri_variables']['slug']) && !isset($uriVariables['slug'])) {
            if ($ws = $this->currentWorkspace->get()) {
                $slug = $ws->getSlug();
                $context['uri_variables']['slug'] = $slug;
                $uriVariables['slug']             = $slug;
            }
        }
    }

    public function getIriFromResource(
        object|string $resourceClass,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        Operation $operation = null,
        array $context = []
    ): string {
        $this->ensureSlug($context);
        return $this->decorated->getIriFromResource($resourceClass, $referenceType, $operation, $context);
    }

    public function getIriFromResourceItem(
        object $item,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH,
        Operation $operation = null,
        array $context = []
    ): string {
        $this->ensureSlug($context);
        return $this->decorated->getIriFromResourceItem($item, $referenceType, $operation, $context);
    }

    public function getIriFromOperation(
        Operation $operation,
        array $uriVariables = [],
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ): string {
        $ctx = [];
        $this->ensureSlug($ctx, $uriVariables);
        return $this->decorated->getIriFromOperation($operation, $uriVariables, $referenceType);
    }

    public function getResourceFromIri(string $iri, array $context = [], ?Operation $operation = null): object
    {
        return $this->decorated->getResourceFromIri($iri, $context);
    }

    public function iriToResource(string $iri, array $context = []): object
    {
        return $this->decorated->iriToResource($iri, $context);
    }
}
