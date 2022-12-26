<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\User\Commands\RemovePatrol;
use App\Model\User\Commands\RemoveTroop;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Repositories\UserGroupRoleRepository;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveTroopHandler implements MessageHandlerInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private EntityManagerInterface $em,
        private UserGroupRoleRepository $userGroupRoleRepository,
        private TroopRepository $troopRepository,
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(RemoveTroop $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            foreach ($command->getTroop()->getPatrols() as $patrol) {
                $this->commandBus->handle(new RemovePatrol($patrol));
            }

            foreach ($command->getTroop()->getUsersRoles() as $userRole) {
                $user = $userRole->getUser();
                $this->userGroupRoleRepository->remove($userRole);
                if ($user->getRoles()->isEmpty() && $user->getGroupRoles()->isEmpty()) {
                    $this->userRepository->remove($user);
                }
            }

            $this->troopRepository->remove($command->getTroop());
        });
    }
}
