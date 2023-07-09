<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\Program\Program;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\User\Queries\UserAllowedProgramsQuery;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserAllowedProgramsQueryHandler implements MessageHandlerInterface
{
    public function __construct(private ProgramRepository $programRepository)
    {
    }

    /** @return Collection<int, Program> */
    public function __invoke(UserAllowedProgramsQuery $query): Collection
    {
        return $this->programRepository->findUserAllowed($query->getUser(), $query->isPaidOnly());
    }
}
