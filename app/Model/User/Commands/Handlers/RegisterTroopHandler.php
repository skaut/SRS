<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Application\Repositories\VariableSymbolRepository;
use App\Model\Application\VariableSymbol;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterTroop;
use App\Model\User\Repositories\TroopRepository;
use App\Model\User\Troop;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

use function str_pad;
use function strval;

use const STR_PAD_LEFT;

class RegisterTroopHandler implements MessageHandlerInterface
{
    public function __construct(
        private QueryBus $queryBus,
        private EntityManagerInterface $em,
        private TroopRepository $troopRepository,
        private VariableSymbolRepository $variableSymbolRepository
    ) {
    }

    public function __invoke(RegisterTroop $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $variableSymbolCode = $this->queryBus->handle(new SettingStringValueQuery(Settings::VARIABLE_SYMBOL_CODE));

            $variableSymbol = new VariableSymbol();
            $this->variableSymbolRepository->save($variableSymbol);

            $variableSymbolText = $variableSymbolCode . str_pad(strval($variableSymbol->getId()), 6, '0', STR_PAD_LEFT);

            $variableSymbol->setVariableSymbol($variableSymbolText);
            $this->variableSymbolRepository->save($variableSymbol);

            $troop = new Troop($command->getLeader(), $variableSymbol);
            $this->troopRepository->save($troop);
        });
    }
}
