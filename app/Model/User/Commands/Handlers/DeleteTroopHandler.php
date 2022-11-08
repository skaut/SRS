<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\User\Commands\DeletePatrol;
use App\Model\User\Commands\DeleteTroop;
use App\Model\User\Queries\TroopByIdQuery;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Repositories\UserGroupRoleRepository;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class DeleteTroopHandler implements MessageHandlerInterface
{
    public function __construct(
        private QueryBus $queryBus,
        private CommandBus $commandBus,
        private EntityManagerInterface $em,
        private TroopRepository $troopRepository,
        private UserGroupRoleRepository $userGroupRoleRepository
    ) {
    }

    public function __invoke(DeleteTroop $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $troop = $this->queryBus->handle(new TroopByIdQuery($command->id));
            foreach ($troop->getPatrols() as $patrol) {
                $this->commandBus->handle(new DeletePatrol($patrol->getId()));
            }

            foreach ($troop->getUsersRoles() as $usersRole) {
                $this->userGroupRoleRepository->remove($usersRole);
            }

            $this->troopRepository->remove($troop);
        });
    }
}
