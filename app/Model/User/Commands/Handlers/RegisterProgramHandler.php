<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Events\ProgramRegisteredEvent;
use App\Model\User\Queries\UserProgramsQuery;
use eGen\MessageBus\Bus\EventBus;
use eGen\MessageBus\Bus\QueryBus;

class RegisterProgramHandler
{
    private EventBus $eventBus;

    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(EventBus $eventBus, ProgramApplicationRepository $programApplicationRepository)
    {
        $this->eventBus                      = $eventBus;
        $this->programApplicationRepository  = $programApplicationRepository;
    }

    public function __invoke(RegisterProgram $command) : void
    {
        $programApplication = $this->programApplicationRepository->findByUserAndProgram($command->getUser(), $command->getProgram());

        if ($programApplication !== null) {
            return; // todo: exception
        }

        $this->programApplicationRepository->save(new ProgramApplication($command->getUser(), $command->getProgram(), $command->isAlternate()));

        $this->eventBus->handle(new ProgramRegisteredEvent($command->getUser(), $command->getProgram(), $command->isAlternate(), $command->isNotifyUser()));
    }
}