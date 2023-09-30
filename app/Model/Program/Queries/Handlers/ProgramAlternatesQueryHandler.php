<?php

declare(strict_types=1);

namespace App\Model\Program\Queries\Handlers;

use App\Model\Program\Queries\ProgramAlternatesQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProgramAlternatesQueryHandler implements MessageHandlerInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    /** @return Collection<int, User> */
    public function __invoke(ProgramAlternatesQuery $query): Collection
    {
        return $this->userRepository->findProgramAlternates($query->getProgram());
    }
}
