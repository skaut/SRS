<?php

declare(strict_types=1);

namespace App\Model\Group\Commands\Handlers;

use App\Model\Group\Commands\RemoveGroup;
use App\Model\Group\Repositories\GroupRepository;
use App\Services\CommandBus;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveGroupHandler implements MessageHandlerInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private EntityManagerInterface $em,
        private GroupRepository $groupRepository,
    ) {
    }

    public function __invoke(RemoveGroup $command): void
    {
        $this->em->wrapInTransaction(function (EntityManager $em) use ($command): void {
            $group = $command->getGroup();

            $this->groupRepository->remove($group);
        });
    }
}
