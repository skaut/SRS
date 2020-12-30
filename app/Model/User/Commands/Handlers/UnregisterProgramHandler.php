<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Events\ProgramUnregisteredEvent;
use App\Model\User\Queries\HasProgramQuery;
use eGen\MessageBus\Bus\EventBus;
use eGen\MessageBus\Bus\QueryBus;

class UnregisterProgramHandler
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

    public function __invoke(UnregisterProgram $command) : void
    {
        if (! $this->queryBus->handle(new HasProgramQuery($command->getUser(), $command->getProgram()))) {
            return;
        }

        $this->programApplicationRepository->remove();

        $this->eventBus->handle(new ProgramUnregisteredEvent($command->getUser(), $command->getProgram(), $command->isNotifyUser()));
    }
}