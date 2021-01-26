<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\SaveProgram;
use App\Model\Program\Events\ProgramCreatedEvent;
use App\Model\Program\Repositories\ProgramRepository;
use App\Services\EventBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveProgramHandler implements MessageHandlerInterface
{
    private EventBus $eventBus;

    private EntityManagerInterface $em;

    private ProgramRepository $programRepository;

    public function __construct(EventBus $eventBus, EntityManagerInterface $em, ProgramRepository $programRepository)
    {
        $this->eventBus          = $eventBus;
        $this->em                = $em;
        $this->programRepository = $programRepository;
    }

    public function __invoke(SaveProgram $command): void
    {
        $program = $command->getProgram();

        if ($program->getId() === null) {
            $this->em->transactional(function () use ($program): void {
                $this->programRepository->save($program);
                $this->eventBus->handle(new ProgramCreatedEvent($program));
            });
        } else {
            $this->programRepository->save($program);
        }
    }
}
