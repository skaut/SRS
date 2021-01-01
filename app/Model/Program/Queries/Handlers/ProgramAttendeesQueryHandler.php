<?php

declare(strict_types=1);

namespace App\Model\Program\Queries\Handlers;

use App\Model\Program\Queries\ProgramAttendeesQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class ProgramAttendeesQueryHandler
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @return Collection<User>
     */
    public function __invoke(ProgramAttendeesQuery $query) : Collection
    {
        return $this->userRepository->findProgramAttendees($query->getProgram());
    }
}
