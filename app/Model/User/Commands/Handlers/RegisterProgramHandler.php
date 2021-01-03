<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Events\ProgramRegisteredEvent;
use Doctrine\DBAL\LockMode;
use eGen\MessageBus\Bus\EventBus;
use http\Client\Curl\User;
use Nettrine\ORM\EntityManagerDecorator;

class RegisterProgramHandler
{
    private EventBus $eventBus;

    private EntityManagerDecorator $em;

    private ProgramRepository $programRepository;

    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(
        EventBus $eventBus,
        EntityManagerDecorator $em,
        ProgramRepository $programRepository,
        ProgramApplicationRepository $programApplicationRepository
    ) {
        $this->eventBus                     = $eventBus;
        $this->em                           = $em;
        $this->programRepository            = $programRepository;
        $this->programApplicationRepository = $programApplicationRepository;
    }

    public function __invoke(RegisterProgram $command) : void
    {
        $this->programApplicationRepository->saveUserProgramApplication($command->getUser(), $command->getProgram()->getId());

        $this->eventBus->handle(new ProgramRegisteredEvent($command->getUser(), $command->getProgram(), $command->isAlternate(), $command->isNotifyUser()));
    }
}
