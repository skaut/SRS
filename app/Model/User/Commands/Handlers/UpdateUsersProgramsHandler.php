<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Commands\UpdateUsersPrograms;
use App\Model\User\Queries\UserAllowedProgramsQuery;
use App\Model\User\Queries\UserProgramsQuery;
use App\Services\CommandBus;
use App\Services\ISettingsService;
use App\Services\QueryBus;
use Nettrine\ORM\EntityManagerDecorator;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UpdateUsersProgramsHandler implements MessageHandlerInterface
{
    private QueryBus $queryBus;

    private CommandBus $commandBus;

    private EntityManagerDecorator $em;

    private ISettingsService $settingsService;

    public function __construct(
        QueryBus $queryBus,
        CommandBus $commandBus,
        EntityManagerDecorator $em,
        ISettingsService $settingsService
    ) {
        $this->queryBus        = $queryBus;
        $this->commandBus      = $commandBus;
        $this->em              = $em;
        $this->settingsService = $settingsService;
    }

    public function __invoke(UpdateUsersPrograms $command): void
    {
        $this->em->transactional(function () use ($command): void {
            $registrationBeforePaymentAllowed = $this->settingsService->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT);

            foreach ($command->getUsers() as $user) {
                $userPrograms        = $this->queryBus->handle(new UserProgramsQuery($user));
                $userAllowedPrograms = $this->queryBus->handle(new UserAllowedProgramsQuery($user, ! $registrationBeforePaymentAllowed));

                // odhlášení programů, na které nemá po změně nárok
                foreach ($userPrograms as $program) {
                    if (! $userAllowedPrograms->contains($program)) {
                        $this->commandBus->handle(new UnregisterProgram($user, $program));
                    }
                }

                // přihlášení automaticky přihlašovaných programů, na které neměl před změnou nárok
                foreach ($userAllowedPrograms as $program) {
                    if ($program->getBlock()->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED && ! $userPrograms->contains($program)) {
                        $this->commandBus->handle(new RegisterProgram($user, $program));
                    }
                }
            }
        });
    }
}
