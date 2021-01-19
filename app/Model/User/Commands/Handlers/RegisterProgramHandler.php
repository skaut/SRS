<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\Program\Exceptions\UserNotAllowedProgramBeforePaymentException;
use App\Model\Program\Exceptions\UserNotAllowedProgramException;
use App\Model\Program\ProgramApplication;
use App\Model\Program\Repositories\ProgramApplicationRepository;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Events\ProgramRegisteredEvent;
use App\Model\User\Queries\UserAllowedProgramsQuery;
use App\Services\EventBus;
use App\Services\ISettingsService;
use App\Services\QueryBus;
use Nettrine\ORM\EntityManagerDecorator;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class RegisterProgramHandler implements MessageHandlerInterface
{
    private QueryBus $queryBus;

    private EventBus $eventBus;

    private EntityManagerDecorator $em;

    private ProgramApplicationRepository $programApplicationRepository;

    private ISettingsService $settingsService;

    public function __construct(
        QueryBus $queryBus,
        EventBus $eventBus,
        EntityManagerDecorator $em,
        ProgramApplicationRepository $programApplicationRepository,
        ISettingsService $settingsService
    ) {
        $this->queryBus                     = $queryBus;
        $this->eventBus                     = $eventBus;
        $this->em                           = $em;
        $this->programApplicationRepository = $programApplicationRepository;
        $this->settingsService              = $settingsService;
    }

    /**
     * @throws UserNotAllowedProgramException
     */
    public function __invoke(RegisterProgram $command): void
    {
        if (! $this->queryBus->handle(new UserAllowedProgramsQuery($command->getUser()))->contains($command->getProgram())) {
            throw new UserNotAllowedProgramException();
        }

        if (! $this->settingsService->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT)
            && (! $command->getUser()->hasPaidSubevent($command->getProgram()->getBlock()->getSubevent()) || ! $command->getUser()->hasPaidRolesApplication())
        ) {
            throw new UserNotAllowedProgramBeforePaymentException();
        }

        $this->em->transactional(function () use ($command): void {
            $programApplication = new ProgramApplication($command->getUser(), $command->getProgram());
            $this->programApplicationRepository->save($programApplication);
            $this->eventBus->handle(new ProgramRegisteredEvent($command->getUser(), $command->getProgram(), $programApplication->isAlternate(), $command->isNotifyUser()));
        });
    }
}
