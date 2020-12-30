<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\Program\Program;
use App\Model\Program\Repositories\ProgramRepository;
use App\Model\User\Queries\UserProgramsQuery;
use Doctrine\Common\Collections\Collection;

class UserProgramsQueryHandler
{
    private ProgramRepository $programRepository;

    public function __construct(ProgramRepository $programRepository)
    {
        $this->programRepository = $programRepository;
    }

    /**
     * @return Collection<Program>
     */
    public function __invoke(UserProgramsQuery $query) : Collection
    {
        return $this->programRepository->findUserRegistered($query->getUser(), $query->isIncludeAlternates());
    }
}