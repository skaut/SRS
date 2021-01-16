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
    private ProgramRepository $programRepository;

    public function __construct(ProgramRepository $programRepository)
    {
        $this->programRepository = $programRepository;
    }

    /**
     * @return Collection<Program>
     */
    public function __invoke(UserAllowedProgramsQuery $query): Collection
    {
        return $this->programRepository->findUserAllowed($query->getUser());
    }
}
