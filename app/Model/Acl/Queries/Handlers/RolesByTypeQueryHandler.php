<?php

declare(strict_types=1);

namespace App\Model\Acl\Queries\Handlers;

use App\Model\Acl\Queries\RolesByTypeQuery;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RolesByTypeQueryHandler implements MessageHandlerInterface
{
    public function __construct(private RoleRepository $roleRepository)
    {
    }

    /**
     * @return Role[]
     */
    public function __invoke(RolesByTypeQuery $query): array
    {
        return $this->roleRepository->findByType($query->getType());
    }
}
