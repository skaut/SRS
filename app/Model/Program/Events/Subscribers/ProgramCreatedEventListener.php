<?php

declare(strict_types=1);

namespace App\Model\Program\Events\Subscribers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Events\ProgramCreatedEvent;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\ISettingsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProgramCreatedEventListener implements MessageHandlerInterface
{
    private CommandBus $commandBus;

    private EntityManagerInterface $em;

    private UserRepository $userRepository;

    private ISettingsService $settingsService;

    public function __construct(
        CommandBus $commandBus,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        ISettingsService $settingsService
    ) {
        $this->commandBus      = $commandBus;
        $this->em              = $em;
        $this->userRepository  = $userRepository;
        $this->settingsService = $settingsService;
    }

    /**
     * Pokud je nový program automaticky zapisovaný, je přidán všem oprávněným uživatelům.
     */
    public function __invoke(ProgramCreatedEvent $event): void
    {
        $this->em->transactional(function () use ($event): void {
            $block = $event->getProgram()->getBlock();

            if ($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED) {
                $registrationBeforePaymentAllowed = $this->settingsService->getBoolValue(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT);

                foreach ($this->userRepository->findBlockAllowed($block, ! $registrationBeforePaymentAllowed) as $user) {
                    $this->commandBus->handle(new RegisterProgram($user, $event->getProgram()));
                }
            }
        });
    }
}
