<?php

declare(strict_types=1);

namespace App\Model\Group\Events\Subscribers;

use App\Model\Enums\GroupMandatoryType;
use App\Model\Group\Events\StatusUpdatedEvent;
use App\Model\Group\Queries\GroupAlternatesQuery;
use App\Model\Group\Queries\GroupAttendeesQuery;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterGroup;
use App\Model\User\Commands\UnregisterGroup;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\QueryBus;
use App\Utils\Helpers;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class StatusUpdatedEventListener implements MessageHandlerInterface
{
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
    ) {
    }

    /**
     * Pokud se změnily registrovatelné role u kategorie, je třeba přihlásit/odhlásit programy účastníků.
     */
    public function __invoke(StatusUpdatedEvent $event): void
    {
        $this->em->wrapInTransaction(function () use ($event): void {
            $registerableRoles    = $event->getStatus()->getRegisterableRoles();
            $registerableRolesOld = $event->getRegisterableRolesOld();

            if (Helpers::collectionsEquals($registerableRoles, $registerableRolesOld)) {
                return;
            }

            foreach ($event->getStatus()->getBlocks() as $block) {
                $registrationBeforePaymentAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT));
                $allowedUsers                     = $this->userRepository->findBlockAllowed($block, ! $registrationBeforePaymentAllowed);

                foreach ($block->getGroups() as $program) {
                    $programAlternates = $this->queryBus->handle(new GroupAlternatesQuery($program));
                    foreach ($programAlternates as $user) {
                        if (! $allowedUsers->contains($user)) {
                            $this->commandBus->handle(new UnregisterGroup($user, $program));
                        }
                    }

                    $programAttendees = $this->queryBus->handle(new GroupAttendeesQuery($program));
                    foreach ($programAttendees as $user) {
                        if (! $allowedUsers->contains($user)) {
                            $this->commandBus->handle(new UnregisterGroup($user, $program));
                        }
                    }

                    if ($block->getMandatory() === GroupMandatoryType::AUTO_REGISTERED) {
                        foreach ($allowedUsers as $user) {
                            if (! $programAttendees->contains($user)) {
                                $this->commandBus->handle(new RegisterGroup($user, $program));
                            }
                        }
                    }
                }
            }
        });
    }
}
