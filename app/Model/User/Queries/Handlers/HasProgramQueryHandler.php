<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Queries\HasProgramQuery;

class HasProgramQueryHandler
{
    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(ProgramApplicationRepository $programApplicationRepository)
    {
        $this->programApplicationRepository = $programApplicationRepository;
    }

    public function __invoke(HasProgramQuery $query) : bool
    {
        $programApplication = $this->programApplicationRepository->findByUserAndProgram($query->getUser(), $query->getProgram());

        if ($programApplication === null) {
            return false;
        }

        return ! $programApplication->isAlternate();
    }
}