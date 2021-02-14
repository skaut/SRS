<?php

declare(strict_types=1);

namespace App\Model\Program\Events\Subscribers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Events\ProgramCreatedEvent;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Repositories\UserRepository;
use App\Services\CommandBus;
use App\Services\QueryBus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class ProgramCreatedEventListener implements MessageHandlerInterface
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
     * Pokud je nový program automaticky zapisovaný, je přidán všem oprávněným uživatelům.
     */
    public function __invoke(ProgramCreatedEvent $event): void
    {
        $this->em->transactional(function () use ($event): void {
            $block = $event->getProgram()->getBlock();

            if ($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED) {
                $registrationBeforePaymentAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::IS_ALLOWED_REGISTER_PROGRAMS_BEFORE_PAYMENT));

                foreach ($this->userRepository->findBlockAllowed($block, ! $registrationBeforePaymentAllowed) as $user) {
                    $this->commandBus->handle(new RegisterProgram($user, $event->getProgram()));
                }
            }
        });
    }
}
