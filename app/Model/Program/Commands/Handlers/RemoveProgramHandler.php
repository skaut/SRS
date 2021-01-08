<?php

declare(strict_types=1);

namespace App\Model\Program\Commands\Handlers;

use App\Model\Program\Commands\RemoveProgram;
use App\Model\Program\Repositories\ProgramRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RemoveProgramHandler implements MessageHandlerInterface
{
    private ProgramRepository $programRepository;

    public function __construct(ProgramRepository $programRepository)
    {
        $this->programRepository = $programRepository;
    }

    public function __invoke(RemoveProgram $command) : void
    {
        $this->programRepository->remove($command->getProgram());
    }
}
