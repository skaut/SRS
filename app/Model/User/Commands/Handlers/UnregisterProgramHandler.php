<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Exceptions\UserNotAttendsProgramException;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Events\ProgramUnregisteredEvent;
use App\Services\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UnregisterProgramHandler implements MessageHandlerInterface
{
    public function __construct(
        private EventBus $eventBus,
        private EntityManagerInterface $em,
        private ProgramApplicationRepository $programApplicationRepository
    ) {
    }

    public function __invoke(UnregisterProgram $command): void
    {
        $programApplication = $this->programApplicationRepository->findByUserAndProgram($command->getUser(), $command->getProgram());
        if ($programApplication === null) {
            throw new UserNotAttendsProgramException();
        }

        $this->em->wrapInTransaction(function () use ($command, $programApplication): void {
            $alternate = $programApplication->isAlternate();
            $this->programApplicationRepository->remove($programApplication);
            $this->eventBus->handle(new ProgramUnregisteredEvent($command->getUser(), $command->getProgram(), $alternate, $command->isNotifyUser()));
        });
    }
}
