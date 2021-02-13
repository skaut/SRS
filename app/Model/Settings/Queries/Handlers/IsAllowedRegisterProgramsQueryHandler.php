<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries\Handlers;

use App\Model\Enums\ProgramRegistrationType;
use App\Model\Settings\Queries\IsAllowedRegisterProgramsQuery;
use App\Model\Settings\Settings;
use App\Services\CommandBus;
use App\Services\QueryBus;
use DateTimeImmutable;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class IsAllowedRegisterProgramsQueryHandler implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    public function __construct(CommandBus $commandBus, QueryBus $queryBus)
    {
        $this->commandBus = $commandBus;
        $this->queryBus   = $queryBus;
    }

    public function __invoke(IsAllowedRegisterProgramsQuery $query): bool
    {
        $registerProgramsType = $this->settingsService->getValue(Settings::REGISTER_PROGRAMS_TYPE);

        if ($registerProgramsType === ProgramRegistrationType::ALLOWED) {
            return true;
        } elseif ($registerProgramsType === ProgramRegistrationType::ALLOWED_FROM_TO) {
            $registerProgramsFrom = $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_FROM);
            $registerProgramsTo   = $this->settingsService->getDateTimeValue(Settings::REGISTER_PROGRAMS_TO);

            return ($registerProgramsFrom === null || $registerProgramsFrom <= new DateTimeImmutable())
                && ($registerProgramsTo === null || $registerProgramsTo >= new DateTimeImmutable());
        }

        return false;
    }
}
