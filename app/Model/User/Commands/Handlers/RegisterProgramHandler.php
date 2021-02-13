<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Exceptions\UserNotAllowedProgramException;
use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Events\ProgramRegisteredEvent;
use App\Model\User\Queries\UserAllowedProgramsQuery;
use App\Services\EventBus;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RegisterProgramHandler implements MessageHandlerInterface
{
    private QueryBus $queryBus;

    private EventBus $eventBus;

    private EntityManagerInterface $em;

    private ProgramApplicationRepository $programApplicationRepository;

    public function __construct(
        QueryBus $queryBus,
        EventBus $eventBus,
        EntityManagerInterface $em,
        ProgramApplicationRepository $programApplicationRepository
    ) {
        $this->queryBus                     = $queryBus;
        $this->eventBus                     = $eventBus;
        $this->em                           = $em;
        $this->programApplicationRepository = $programApplicationRepository;
    }

    /**
     * @throws UserNotAllowedProgramException
     */
    public function __invoke(RegisterProgram $command): void
    {
        $registrationBeforePaymentAllowed = $this->settingsService->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT);
        if (! $this->queryBus->handle(new UserAllowedProgramsQuery($command->getUser(), ! $registrationBeforePaymentAllowed))->contains($command->getProgram())) {
            throw new UserNotAllowedProgramException();
        }

        $this->em->transactional(function () use ($command): void {
            $programApplication = new ProgramApplication($command->getUser(), $command->getProgram());
            $this->programApplicationRepository->save($programApplication);
            $this->eventBus->handle(new ProgramRegisteredEvent($command->getUser(), $command->getProgram(), $programApplication->isAlternate(), $command->isNotifyUser()));
        });
    }
}
