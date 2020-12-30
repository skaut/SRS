<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\UpdateUserPrograms;
use App\Services\MailService;
use App\Services\SettingsService;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;

class UpdateUserProgramsHandler // todo: nahradit pomoci event?
{
    private QueryBus $queryBus;

    private CommandBus $commandBus;

    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(
        QueryBus $queryBus,
        ProgramApplicationRepository $programApplicationRepository
    ) {
        $this->queryBus                      = $queryBus;
        $this->programApplicationRepository  = $programApplicationRepository;
    }

    public function __invoke(UpdateUserPrograms $command) : void
    {
        foreach ($users as $user) {
            $oldUsersPrograms    = clone $user->getPrograms();
            $userAllowedPrograms = $this->getUserAllowedPrograms($user);

            foreach ($oldUsersPrograms as $program) {
                if (! $userAllowedPrograms->contains($program)) {
                    $this->unregisterProgramImpl($user, $program);
                }
            }

            foreach ($userAllowedPrograms as $program) {
                if ($program->getBlock()->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED) {
                    $this->registerProgramImpl($user, $program);
                }
            }

            //todo: nahradnici
        }
    }
}