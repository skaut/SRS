<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\User\Queries\TroopByIdQuery;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Troop;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class TroopByIdQueryHandler implements MessageHandlerInterface
{
    public function __construct(private TroopRepository $troopRepository)
    {
    }

    public function __invoke(TroopByIdQuery $query): ?Troop
    {
        return $this->troopRepository->findById($query->getTroopId());
    }
}
