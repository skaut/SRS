<?php

declare(strict_types=1);

namespace App\Model\Program\Events\Subscribers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Events\CategoryUpdatedEvent;
use App\Model\Program\Queries\ProgramAlternatesQuery;
use App\Model\Program\Queries\ProgramAttendeesQuery;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Utils\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CategoryUpdatedEventListener implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    private EntityManagerInterface $em;

    private UserRepository $userRepository;

    public function __construct(
        CommandBus $commandBus,
        QueryBus $queryBus,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ) {
        $this->commandBus     = $commandBus;
        $this->queryBus       = $queryBus;
        $this->em             = $em;
        $this->userRepository = $userRepository;
    }

    /**
     * Pokud se změnily registrovatelné role u kategorie, je třeba přihlásit/odhlásit programy účastníků.
     */
    public function __invoke(CategoryUpdatedEvent $event): void
    {
        $this->em->wrapInTransaction(function () use ($event): void {
            $registerableRoles    = $event->getCategory()->getRegisterableRoles();
            $registerableRolesOld = $event->getRegisterableRolesOld();

            if (Helpers::collectionsEquals($registerableRoles, $registerableRolesOld)) {
                return;
            }

            foreach ($event->getCategory()->getBlocks() as $block) {
                $registrationBeforePaymentAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT));
                $allowedUsers                     = $this->userRepository->findBlockAllowed($block, ! $registrationBeforePaymentAllowed);

                foreach ($block->getPrograms() as $program) {
                    $programAlternates = $this->queryBus->handle(new ProgramAlternatesQuery($program));
                    foreach ($programAlternates as $user) {
                        if (! $allowedUsers->contains($user)) {
                            $this->commandBus->handle(new UnregisterProgram($user, $program));
                        }
                    }

                    $programAttendees = $this->queryBus->handle(new ProgramAttendeesQuery($program));
                    foreach ($programAttendees as $user) {
                        if (! $allowedUsers->contains($user)) {
                            $this->commandBus->handle(new UnregisterProgram($user, $program));
                        }
                    }

                    if ($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED) {
                        foreach ($allowedUsers as $user) {
                            if (! $programAttendees->contains($user)) {
                                $this->commandBus->handle(new RegisterProgram($user, $program));
                            }
                        }
                    }
                }
            }
        });
    }
}
