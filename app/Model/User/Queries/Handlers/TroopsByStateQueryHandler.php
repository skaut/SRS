<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\User\Queries\TroopsByStateQuery;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Troop;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class TroopsByStateQueryHandler implements MessageHandlerInterface
{
    public function __construct(private TroopRepository $troopRepository)
    {
    }

    /**
     * @return Collection<int, Troop>
     */
    public function __invoke(TroopsByStateQuery $query): Collection
    {
        return $this->troopRepository->findByState($query->getTroopState());
    }
}
