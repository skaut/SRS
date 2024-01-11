<?php

declare(strict_types=1);

namespace App\Model\Group\Handlers;

use App\Model\Group\Commands\RemoveStatus;
use App\Model\Group\Repositories\StatusRepository;
use App\Services\CommandBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveStatusHandler implements MessageHandlerInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private EntityManagerInterface $em,
        private StatusRepository $statusRepository,
    ) {
    }

    public function __invoke(RemoveStatus $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $status = $command->getStatus();

            $this->statusRepository->remove($status);
        });
    }
}
