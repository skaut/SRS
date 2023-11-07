<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\Program\Program;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\User\Queries\UserAttendsProgramsQuery;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserAttendsProgramsQueryHandler implements MessageHandlerInterface
{
    public function __construct(private readonly ProgramRepository $programRepository)
    {
    }

    /** @return Collection<int, Program> */
    public function __invoke(UserAttendsProgramsQuery $query): Collection
    {
        return $this->programRepository->findUserAttends($query->getUser());
    }
}
