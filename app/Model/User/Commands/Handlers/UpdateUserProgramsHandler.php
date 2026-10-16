<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Repositories\ProgramApplicationRepository;
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
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
        private readonly EntityManagerInterface $em,
        private readonly ProgramApplicationRepository $programApplicationRepository,
    ) {
    }

    public function __invoke(UpdateUserPrograms $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $registrationBeforePaymentAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT));

            $userPrograms        = $this->queryBus->handle(new UserAttendsProgramsQuery($command->getUser()));
            $userAllowedPrograms = $this->queryBus->handle(new UserAllowedProgramsQuery($command->getUser(), ! $registrationBeforePaymentAllowed));

            // odhlášení programů, na které nemá po změně nárok
            foreach ($userPrograms as $program) {
                if (! $userAllowedPrograms->contains($program)) {
                    $this->commandBus->handle(new UnregisterProgram($command->getUser(), $program));
                }
            }

            // Přihlášky včetně náhradnických. userPrograms obsahuje jen programy, kde je
            // uživatel účastníkem, přihlašovat ho znovu na program, kde je náhradníkem,
            // by skončilo výjimkou.
            $userApplications = $this->programApplicationRepository->findUserApplicationsAlternateFlags($command->getUser());

            // přihlášení automaticky přihlašovaných programů, na které neměl před změnou nárok
            foreach ($userAllowedPrograms as $program) {
                if ($program->getBlock()->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED && ! isset($userApplications[$program->getId()])) {
                    $this->commandBus->handle(new RegisterProgram($command->getUser(), $program));
                }
            }
        });
    }
}
