<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Commands\UpdateUsersPrograms;
use App\Model\User\Queries\UserAllowedProgramsQuery;
use App\Model\User\Queries\UserProgramsQuery;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Nettrine\ORM\EntityManagerDecorator;

class UpdateUsersProgramsHandler
{
    private QueryBus $queryBus;

    private CommandBus $commandBus;

    private EntityManagerDecorator $em;

    public function __construct(QueryBus $queryBus, CommandBus $commandBus, EntityManagerDecorator $em)
    {
        $this->queryBus   = $queryBus;
        $this->commandBus = $commandBus;
        $this->em         = $em;
    }

    public function __invoke(UpdateUsersPrograms $command) : void
    {
        $this->em->transactional(function () use ($command) : void {
            foreach ($command->getUsers() as $user) {
                $userPrograms        = $this->queryBus->handle(new UserProgramsQuery($user));
                $userAllowedPrograms = $this->queryBus->handle(new UserAllowedProgramsQuery($user));

                // odhlášení programů, na které nemá po změně nárok
                foreach ($userPrograms as $program) {
                    if (! $userAllowedPrograms->contains($program)) {
                        $this->commandBus->handle(new UnregisterProgram($user, $program, true));
                    }
                }

                // přihlášení automaticky přihlašovaných programů, na které neměl před změnou nárok
                foreach ($userAllowedPrograms as $program) {
                    if ($program->getBlock()->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED && ! $userPrograms->contains($program)) {
                        $this->commandBus->handle(new RegisterProgram($user, $program, false, true));
                    }
                }
            }
        });
    }
}
