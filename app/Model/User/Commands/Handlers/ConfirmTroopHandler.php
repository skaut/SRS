<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Enums\MaturityType;
use App\Model\Enums\TroopApplicationState;
use App\Model\Mailing\Template;
use App\Model\Mailing\TemplateVariable;
use App\Model\Settings\Queries\SettingDateValueQuery;
use App\Model\Settings\Queries\SettingIntValueQuery;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\ConfirmTroop;
use App\Model\User\Repositories\TroopRepository;
use App\Services\MailService;
use App\Services\QueryBus;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Yasumi\Yasumi;

class ConfirmTroopHandler implements MessageHandlerInterface
{
    public function __construct(private QueryBus $queryBus, private TroopRepository $troopRepository, private MailService $mailService)
    {
    }

    public function __invoke(ConfirmTroop $command): void
    {
        $troop = $this->troopRepository->findById($command->getTroopId());
        $troop->setState(TroopApplicationState::WAITING_FOR_PAYMENT);
        $troop->setPairedTroopCode($command->getPairedTroopCode());
        $troop->setFee($troop->countFee());
        $troop->setApplicationDate(new DateTimeImmutable());
        $troop->setMaturityDate($this->countMaturityDate());
        $this->troopRepository->save($troop);

        $this->mailService->sendMailFromTemplate(new ArrayCollection([$troop->getLeader()]), null, Template::TROOP_REGISTRATION, [
            TemplateVariable::SEMINAR_NAME => $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME)),
            TemplateVariable::APPLICATION_FEE => $troop->getFee(),
            TemplateVariable::APPLICATION_VARIABLE_SYMBOL => $troop->getVariableSymbol()->getVariableSymbol(),
            TemplateVariable::APPLICATION_MATURITY => $troop->getMaturityDateText(),
            TemplateVariable::BANK_ACCOUNT => $this->queryBus->handle(
                new SettingStringValueQuery(Settings::ACCOUNT_NUMBER)
            ),
        ]);
    }

    /**
     * Vypočítá datum splatnosti podle zvolené metody.
     */
    private function countMaturityDate(): ?DateTimeImmutable
    {
        switch (
            $this->queryBus->handle(
                new SettingStringValueQuery(Settings::MATURITY_TYPE)
            )
        ) {
            case MaturityType::DATE:
                return $this->queryBus->handle(new SettingDateValueQuery(Settings::MATURITY_DATE));

            case MaturityType::DAYS:
                return (new DateTimeImmutable())->modify('+' . $this->queryBus->handle(new SettingIntValueQuery(Settings::MATURITY_DAYS)) . ' days');

            case MaturityType::WORK_DAYS:
                $workDays = $this->queryBus->handle(new SettingIntValueQuery(Settings::MATURITY_WORK_DAYS));
                $date     = new DateTimeImmutable();

                for ($i = 0; $i < $workDays;) {
                    $date     = $date->modify('+1 days');
                    $holidays = Yasumi::create('CzechRepublic', (int) $date->format('Y'));

                    if ($holidays->isWorkingDay($date)) {
                        $i++;
                    }
                }

                return $date;
        }

        return null;
    }
}
