<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Events\ProgramUnregisteredEvent;
use eGen\MessageBus\Bus\EventBus;

class UnregisterProgramHandler
{
    private EventBus $eventBus;

    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(EventBus $eventBus, ProgramApplicationRepository $programApplicationRepository)
    {
        $this->eventBus                     = $eventBus;
        $this->programApplicationRepository = $programApplicationRepository;
    }

    public function __invoke(UnregisterProgram $command) : void
    {
        $this->programApplicationRepository->removeUserProgramApplication($command->getUser(), $command->getProgram()->getId());

        $this->eventBus->handle(new ProgramUnregisteredEvent($command->getUser(), $command->getProgram(), $command->isNotifyUser()));
    }
}
