<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Enums\TroopApplicationState;
use App\Model\User\Commands\ConfirmTroop;
use App\Model\User\Repositories\TroopRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ConfirmTroopHandler implements MessageHandlerInterface
{
    public function __construct(private TroopRepository $troopRepository)
    {
    }

    public function __invoke(ConfirmTroop $command): void
    {
        $troop = $this->troopRepository->findById($command->getTroopId());
        $troop->setState(TroopApplicationState::WAITING_FOR_PAYMENT);
        $troop->setPairedTroopCode($command->getPairedTroopCode());
        $troop->setFee($troop->countFee());
        $this->troopRepository->save($troop);
    }
}
