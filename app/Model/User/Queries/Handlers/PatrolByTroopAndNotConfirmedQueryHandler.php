<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\User\Patrol;
use App\Model\User\Queries\PatrolByTroopAndNotConfirmedQuery;
use App\Model\User\Repositories\PatrolRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PatrolByTroopAndNotConfirmedQueryHandler implements MessageHandlerInterface
{
    public function __construct(private PatrolRepository $patrolRepository)
    {
    }

    public function __invoke(PatrolByTroopAndNotConfirmedQuery $query): ?Patrol
    {
        return $this->patrolRepository->findByTroopAndNotConfirmed($query->getTroopId());
    }
}
