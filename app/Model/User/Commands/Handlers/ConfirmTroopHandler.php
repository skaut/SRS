<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Commands\RegisterProgram;
use App\Services\EventBus;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ConfirmTroopHandler implements MessageHandlerInterface
{
    public function __construct(
        private QueryBus $queryBus,
        private EventBus $eventBus,
        private EntityManagerInterface $em,
        private ProgramApplicationRepository $programApplicationRepository
    ) {
    }

    public function __invoke(RegisterProgram $command): void
    {
    }
}
