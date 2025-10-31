<?php

namespace App\Context;

use App\Entity\Workspace;

final class CurrentWorkspace
{
    private ?Workspace $workspace = null;

    public function set(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    public function get(): ?Workspace
    {
        return $this->workspace;
    }
}
