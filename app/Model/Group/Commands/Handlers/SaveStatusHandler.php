<?php

declare(strict_types=1);

namespace App\Model\Group\Commands\Handlers;

use App\Model\Group\Commands\SaveStatus;
use App\Model\Group\Repositories\StatusRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveStatusHandler implements MessageHandlerInterface
{
    public function __construct(private StatusRepository $statusRepository)
    {
    }

    public function __invoke(SaveStatus $command): void
    {
        $this->statusRepository->save($command->getStatus());
    }
}

/*
namespace App\Model\Group\Commands\Handlers;

use App\Model\Group\Commands\SaveStatus;
use App\Model\Group\Events\StatusUpdatedEvent;
use App\Model\Group\Repositories\StatusRepository;
use App\Services\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveStatusHandler implements MessageHandlerInterface
{
    public function __construct(
        private EventBus $eventBus,
        private EntityManagerInterface $em,
        private StatusRepository $statusRepository,
    ) {
    }

    public function __invoke(SaveStatus $command): void
    {
        $status    = $command->getStatus();
//        $statusOld = $command->getStatusOld();

        if ($status->getId() === null) {
            $this->statusRepository->save($status);
        } else {
            $this->em->wrapInTransaction(function () use ($status, $statusOld): void {

                $this->statusRepository->save($status);

                $this->eventBus->handle(new StatusUpdatedEvent($status, $registerableRolesOld));
            });
        }
    }
}
*/
