<?php


namespace App\EventSubscriber;

use App\Context\CurrentWorkspace;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class WorkspaceFilterSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ManagerRegistry  $doctrine,
        private CurrentWorkspace $currentWorkspace
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => 'enableFilter'];
    }

    public function enableFilter(): void
    {
        $ws = $this->currentWorkspace->get();
        if (!$ws) {
            return; // routes non scopées
        }

        $em = $this->doctrine->getManager();
        $filter = $em->getFilters()->enable('workspace_filter');
        $filter->setParameter('ws_id', $ws->getId());
    }
}
