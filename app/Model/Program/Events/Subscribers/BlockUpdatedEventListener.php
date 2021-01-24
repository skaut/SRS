<?php

declare(strict_types=1);

namespace App\Model\Program\Events\Subscribers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Events\BlockUpdatedEvent;
use App\Model\Program\Queries\ProgramAlternatesQuery;
use App\Model\Program\Queries\ProgramAttendeesQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\ISettingsService;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class BlockUpdatedEventListener implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    private EntityManagerInterface $em;

    private UserRepository $userRepository;

    private ISettingsService $settingsService;

    public function __construct(
        CommandBus $commandBus,
        QueryBus $queryBus,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        ISettingsService $settingsService
    ) {
        $this->commandBus      = $commandBus;
        $this->queryBus        = $queryBus;
        $this->em              = $em;
        $this->userRepository  = $userRepository;
        $this->settingsService = $settingsService;
    }

    public function __invoke(BlockUpdatedEvent $event): void
    {
        $this->em->transactional(function () use ($event): void {
            $block     = $event->getBlock();
            $category  = $block->getCategory();
            $subevent  = $block->getSubevent();
            $mandatory = $block->getMandatory();

            $categoryOld  = $event->getCategoryOld();
            $subeventOld  = $event->getSubeventOld();
            $mandatoryOld = $event->getMandatoryOld();

            $registrationBeforePaymentAllowed = $this->settingsService->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT);
            $allowedUsers                     = $this->userRepository->findBlockAllowed($block, ! $registrationBeforePaymentAllowed);

            // aktualizace ucastniku pri zmene kategorie nebo podakce (odstraneni neopravnenych, pridani u automaticky registrovanych)
            if (
                ($category === null && $categoryOld !== null)
                || ($category !== null && $categoryOld === null)
                || ($category !== null && $categoryOld !== null && $category->getId() !== $categoryOld->getId())
                || ($subevent->getId() !== $subeventOld->getId())
            ) {
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

                    if ($mandatory === ProgramMandatoryType::AUTO_REGISTERED) {
                        foreach ($allowedUsers as $user) {
                            if (! $programAttendees->contains($user)) {
                                $this->commandBus->handle(new RegisterProgram($user, $program));
                            }
                        }
                    }
                }
            }

            // odstraneni ucastniku, pokud se odstrani automaticke prihlasovani
            if ($mandatoryOld === ProgramMandatoryType::AUTO_REGISTERED && $mandatory !== ProgramMandatoryType::AUTO_REGISTERED) {
                foreach ($block->getPrograms() as $program) {
                    $programAttendees = $this->queryBus->handle(new ProgramAttendeesQuery($program));
                    foreach ($programAttendees as $user) {
                        $this->commandBus->handle(new UnregisterProgram($user, $program));
                    }
                }
            }

            // pridani ucastniku, pokud je pridano automaticke prihlaseni
            if ($mandatoryOld !== ProgramMandatoryType::AUTO_REGISTERED && $mandatory === ProgramMandatoryType::AUTO_REGISTERED) {
                foreach ($block->getPrograms() as $program) {
                    foreach ($allowedUsers as $user) {
                        $this->commandBus->handle(new RegisterProgram($user, $program));
                    }
                }
            }
        });
    }
}
