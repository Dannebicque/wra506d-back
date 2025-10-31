<?php


namespace App\EventSubscriber;

use App\Context\CurrentWorkspace;
use App\Repository\WorkspaceRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class WorkspaceResolverSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private WorkspaceRepository $workspaces,
        private CurrentWorkspace    $context
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return ['kernel.request' => 'onKernelRequest'];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $req = $event->getRequest();
        $slug = $req->attributes->get('slug');

        if (!$slug) {
            return; // ex: /auth/login global ou docs API
        }

        $ws = $this->workspaces->findOneBy(['slug' => $slug]);

        if (!$ws) {
            throw new NotFoundHttpException('Workspace not found.');
        }

        $this->context->set($ws);
    }
}
