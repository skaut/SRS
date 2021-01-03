<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Exceptions\UserNotAttendsProgramException;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Events\ProgramUnregisteredEvent;
use eGen\MessageBus\Bus\EventBus;
use Nettrine\ORM\EntityManagerDecorator;

class UnregisterProgramHandler
{
    private EventBus $eventBus;

    private EntityManagerDecorator $em;

    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(
        EventBus $eventBus,
        EntityManagerDecorator $em,
        ProgramApplicationRepository $programApplicationRepository
    ) {
        $this->eventBus                     = $eventBus;
        $this->em                           = $em;
        $this->programApplicationRepository = $programApplicationRepository;
    }

    public function __invoke(UnregisterProgram $command) : void
    {
        $programApplication = $this->programApplicationRepository->findUserProgramApplication($command->getUser(), $command->getProgram());
        if ($programApplication === null) {
            throw new UserNotAttendsProgramException();
        }

        $this->em->transactional(function () use ($command, $programApplication) : void {
            $this->programApplicationRepository->removeUserProgramApplication($command->getUser(), $command->getProgram());
            $this->eventBus->handle(new ProgramUnregisteredEvent($command->getUser(), $command->getProgram(), $programApplication->isAlternate(), $command->isNotifyUser()));
        });
    }
}
