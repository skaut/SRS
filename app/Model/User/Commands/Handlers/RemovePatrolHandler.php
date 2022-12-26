<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\User\Commands\RemovePatrol;
use App\Model\User\Repositories\PatrolRepository;
use App\Model\User\Repositories\UserGroupRoleRepository;
use App\Model\User\Repositories\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemovePatrolHandler implements MessageHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserGroupRoleRepository $userGroupRoleRepository,
        private PatrolRepository $patrolRepository,
        private UserRepository $userRepository
    ) {
    }

    public function __invoke(RemovePatrol $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            foreach ($command->getPatrol()->getUsersRoles() as $userRole) {
                $user = $userRole->getUser();
                $this->userGroupRoleRepository->remove($userRole);
                if ($user->getRoles()->isEmpty() && $user->getGroupRoles()->isEmpty()) {
                    $this->userRepository->remove($user);
                }
            }

            $this->patrolRepository->remove($command->getPatrol());
        });
    }
}
