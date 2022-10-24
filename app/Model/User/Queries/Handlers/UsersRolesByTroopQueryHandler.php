<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\User\Queries\UsersRolesByTroopQuery;
use App\Model\User\Repositories\UserGroupRoleRepository;
use App\Model\User\UserGroupRole;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UsersRolesByTroopQueryHandler implements MessageHandlerInterface
{
    public function __construct(private UserGroupRoleRepository $userGroupRoleRepository)
    {
    }

    /**
     * @return Collection<int, UserGroupRole>
     */
    public function __invoke(UsersRolesByTroopQuery $query): Collection
    {
        return $this->userGroupRoleRepository->findByTroop($query->getTroopId());
    }
}
