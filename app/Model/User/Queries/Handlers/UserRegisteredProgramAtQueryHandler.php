<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\User\Queries\UserRegisteredProgramAtQuery;
use DateTimeImmutable;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserRegisteredProgramAtQueryHandler implements MessageHandlerInterface
{
    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(ProgramApplicationRepository $programApplicationRepository)
    {
        $this->programApplicationRepository = $programApplicationRepository;
    }

    public function __invoke(UserRegisteredProgramAtQuery $query): ?DateTimeImmutable
    {
        $programApplication = $this->programApplicationRepository->findByUserAndProgram($query->getUser(), $query->getProgram());

        return $programApplication === null ? null : $programApplication->getCreatedAt();
    }
}
