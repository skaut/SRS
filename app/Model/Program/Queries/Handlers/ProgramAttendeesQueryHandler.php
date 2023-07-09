<?php

declare(strict_types=1);

namespace App\Model\Program\Queries\Handlers;

use App\Model\Program\Queries\ProgramAttendeesQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProgramAttendeesQueryHandler implements MessageHandlerInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /** @return Collection<int, User> */
    public function __invoke(ProgramAttendeesQuery $query): Collection
    {
        return $this->userRepository->findProgramAttendees($query->getProgram());
    }
}
