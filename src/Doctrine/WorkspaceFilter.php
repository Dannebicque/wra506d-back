<?php


namespace App\Doctrine;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class WorkspaceFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $meta, $alias): string
    {
        if (!$meta->hasAssociation('workspace')) {
            return '';
        }

        return sprintf('%s.workspace_id = %s', $alias, $this->getParameter('ws_id'));
    }
}
