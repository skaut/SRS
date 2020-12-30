<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Events\ProgramRegisteredEvent;
use App\Model\User\Queries\HasProgramQuery;
use eGen\MessageBus\Bus\EventBus;
use eGen\MessageBus\Bus\QueryBus;

class RegisterProgramHandler
{
    private QueryBus $queryBus;

    private EventBus $eventBus;

    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(
        QueryBus $queryBus,
        EventBus $eventBus,
        ProgramApplicationRepository $programApplicationRepository
    ) {
        $this->queryBus                      = $queryBus;
        $this->eventBus                      = $eventBus;
        $this->programApplicationRepository  = $programApplicationRepository;
    }

    public function __invoke(RegisterProgram $command) : void
    {
        if ($this->queryBus->handle(new HasProgramQuery($command->getUser(), $command->getProgram()))) {
            return;
        }

        $this->programApplicationRepository->save(new ProgramApplication($command->getUser(), $command->getProgram()));

        $this->eventBus->handle(new ProgramRegisteredEvent($command->getUser(), $command->getProgram(), $command->isAlternate(), $command->isNotifyUser()));
    }
}