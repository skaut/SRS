<?php

declare(strict_types=1);

namespace App\Model\Program\Queries\Handlers;

use App\Model\Program\Queries\ProgramAlternatesCountQuery;
use App\Model\User\Repositories\UserRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProgramAlternatesCountQueryHandler implements MessageHandlerInterface
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function __invoke(ProgramAlternatesCountQuery $query): int
    {
        return $this->userRepository->countProgramAlternates($query->getProgram());
    }
}
