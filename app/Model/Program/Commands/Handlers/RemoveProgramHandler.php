<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\RemoveProgram;
use App\Model\Program\Queries\ProgramAlternatesQuery;
use App\Model\Program\Queries\ProgramAttendeesQuery;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\User\Commands\UnregisterProgram;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveProgramHandler implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    private EntityManagerInterface $em;

    private ProgramRepository $programRepository;

    public function __construct(
        CommandBus $commandBus,
        QueryBus $queryBus,
        EntityManagerInterface $em,
        ProgramRepository $programRepository
    ) {
        $this->commandBus        = $commandBus;
        $this->queryBus          = $queryBus;
        $this->em                = $em;
        $this->programRepository = $programRepository;
    }

    public function __invoke(RemoveProgram $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $program = $command->getProgram();

            $alternates = $this->queryBus->handle(new ProgramAlternatesQuery($program));
            foreach ($alternates as $user) {
                $this->commandBus->handle(new UnregisterProgram($user, $program));
            }

            $attendees = $this->queryBus->handle(new ProgramAttendeesQuery($program));
            foreach ($attendees as $user) {
                $this->commandBus->handle(new UnregisterProgram($user, $program));
            }

            $this->programRepository->remove($command->getProgram());
        });
    }
}
