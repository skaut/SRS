<?php

declare(strict_types=1);

namespace App\Model\Program\Events\Subscribers;

use App\Model\Acl\Events\CategoryUpdatedEvent;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Events\ProgramCreatedEvent;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UpdateUsersPrograms;
use App\Model\User\Repositories\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\CommandBus;
use Nettrine\ORM\EntityManagerDecorator;

class ProgramCreatedEventListener
{
    private CommandBus $commandBus;

    private EntityManagerDecorator $em;

    private UserRepository $userRepository;

    public function __construct(CommandBus $commandBus, EntityManagerDecorator $em, UserRepository $userRepository)
    {
        $this->commandBus     = $commandBus;
        $this->em             = $em;
        $this->userRepository = $userRepository;
    }

    /**
     * Pokud je nový program automaticky zapisovaný, je přidán všem oprávněným uživatelům.
     */
    public function __invoke(ProgramCreatedEvent $event) : void
    {
        $this->em->transactional(function () use ($event) : void {
            $block = $event->getProgram()->getBlock();
            if ($block->getMandatory() === ProgramMandatoryType::AUTO_REGISTERED) {
                foreach ($this->userRepository->findBlockAllowed($block) as $user) {
                    $this->commandBus->handle(new RegisterProgram($user, $event->getProgram(), false, true));
                }
            }
        });
    }
}