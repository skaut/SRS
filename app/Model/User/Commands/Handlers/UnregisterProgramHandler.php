<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Events\ProgramUnregisteredEvent;
use App\Model\User\Queries\UserProgramsQuery;
use eGen\MessageBus\Bus\EventBus;
use eGen\MessageBus\Bus\QueryBus;

class UnregisterProgramHandler
{
    private EventBus $eventBus;

    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(EventBus $eventBus, ProgramApplicationRepository $programApplicationRepository)
    {
        $this->eventBus                      = $eventBus;
        $this->programApplicationRepository  = $programApplicationRepository;
    }

    public function __invoke(UnregisterProgram $command) : void
    {
        $programApplication = $this->programApplicationRepository->findByUserAndProgram($command->getUser(), $command->getProgram());

        if ($programApplication === null) {
            return; // todo: exception
        }

        $this->programApplicationRepository->remove($programApplication);

        $this->eventBus->handle(new ProgramUnregisteredEvent($command->getUser(), $command->getProgram(), $command->isNotifyUser()));
    }
}