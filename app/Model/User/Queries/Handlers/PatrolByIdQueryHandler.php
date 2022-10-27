<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\User\Patrol;
use App\Model\User\Queries\PatrolByIdQuery;
use App\Model\User\Repositories\PatrolRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class PatrolByIdQueryHandler implements MessageHandlerInterface
{
    public function __construct(private PatrolRepository $patrolRepository)
    {
    }

    public function __invoke(PatrolByIdQuery $query): ?Patrol
    {
        return $this->patrolRepository->findById($query->getPatrolId());
    }
}
