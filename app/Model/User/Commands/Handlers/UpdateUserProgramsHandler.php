<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Commands\UpdateUserPrograms;
use App\Model\User\Queries\UserAllowedProgramsQuery;
use App\Model\User\Queries\UserAttendsProgramsQuery;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateUserProgramsHandler implements MessageHandlerInterface
{
    private QueryBus $queryBus;

    private CommandBus $commandBus;

    private EntityManagerInterface $em;

    public function __construct(
        QueryBus $queryBus,
        CommandBus $commandBus,
        EntityManagerInterface $em
    ) {
        $this->queryBus        = $queryBus;
        $this->commandBus      = $commandBus;
        $this->em              = $em;
    }

    public function __invoke(UpdateUserPrograms $command): void
    {
        $this->em->transactional(function () use ($command): void {
            $registrationBeforePaymentAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT));

            $userPrograms        = $this->queryBus->handle(new UserAttendsProgramsQuery($command->getUser()));
            $userAllowedPrograms = $this->queryBus->handle(new UserAllowedProgramsQuery($command->getUser(), ! $registrationBeforePaymentAllowed));

            // odhlášení programů, na které nemá po změně nárok
            foreach ($userPrograms as $program) {
                if (! $userAllowedPrograms->contains($program)) {
                    $this->commandBus->handle(new UnregisterProgram($command->getUser(), $program));
                }
            }

            // přihlášení automaticky přihlašovaných programů, na které neměl před změnou nárok
            foreach ($userAllowedPrograms as $program) {
                if ($program->getBlock()->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED && ! $userPrograms->contains($program)) {
                    $this->commandBus->handle(new RegisterProgram($command->getUser(), $program));
                }
            }
        });
    }
}
