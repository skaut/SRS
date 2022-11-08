<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\User\Commands\DeletePatrol;
use App\Model\User\Queries\PatrolByIdQuery;
use App\Model\User\Repositories\PatrolRepository;
use App\Model\User\Repositories\UserGroupRoleRepository;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DeletePatrolHandler implements MessageHandlerInterface
{
    public function __construct(
        private QueryBus $queryBus,
        private EntityManagerInterface $em,
        private PatrolRepository $patrolRepository,
        private UserGroupRoleRepository $userGroupRoleRepository
    ) {
    }

    public function __invoke(DeletePatrol $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $patrol = $this->queryBus->handle(new PatrolByIdQuery($command->id));
            foreach ($patrol->getUsersRoles() as $usersRole) {
                $this->userGroupRoleRepository->remove($usersRole);
            }

            $this->patrolRepository->remove($patrol);
        });
    }
}
