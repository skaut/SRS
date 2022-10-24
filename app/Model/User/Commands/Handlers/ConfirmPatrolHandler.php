<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\ConfirmPatrol;
use App\Model\User\Repositories\PatrolRepository;
use App\Services\EventBus;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ConfirmPatrolHandler implements MessageHandlerInterface
{
    public function __construct(
        private PatrolRepository $patrolRepository,
        private QueryBus $queryBus,
        private EventBus $eventBus,
        private EntityManagerInterface $em,
        private ProgramApplicationRepository $programApplicationRepository
    ) {
    }

    public function __invoke(ConfirmPatrol $command): void
    {
        $patrol = $this->patrolRepository->findById($command->getPatrolId());
        $patrol->setConfirmed(true);
        $this->patrolRepository->save($patrol);
    }
}
