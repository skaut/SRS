<?php

declare(strict_types=1);

namespace App\Model\Program\Events\Subscribers;

use App\Model\Acl\Events\CategoryUpdatedEvent;
use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Events\BlockUpdatedEvent;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Commands\UpdateUsersPrograms;
use App\Model\User\Repositories\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\CommandBus;
use Nettrine\ORM\EntityManagerDecorator;

class BlockUpdatedEventListener
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

    public function __invoke(BlockUpdatedEvent $event) : void
    {
        $this->em->transactional(function () use ($event) : void {
            $block     = $event->getBlock();
            $category  = $block->getCategory();
            $subevent  = $block->getSubevent();
            $mandatory = $block->getMandatory();

            $originalCategory  = $event->getOriginalCategory();
            $originalSubevent  = $event->getOriginalSubevent();
            $originalMandatory = $event->getOriginalMandatory();

            //aktualizace ucastniku pri zmene kategorie nebo podakce
            if (($category === null && $originalCategory !== null)
                || ($category !== null && $originalCategory === null)
                || ($category !== null && $originalCategory !== null && $category->getId() !== $originalCategory->getId())
                || ($subevent->getId() !== $originalSubevent->getId())) {
                $allowedUsers = $this->userRepository->findBlockAllowed($block);

                foreach ($block->getPrograms() as $program) {
                    foreach ($program->getAttendees() as $user) {
                        if (!$allowedUsers->contains($user)) {
                            $this->commandBus->handle(new UnregisterProgram($user, $program, true));
                        }
                    }
                    if ($mandatory === ProgramMandatoryType::AUTO_REGISTERED) {
                        foreach ($allowedUsers as $user) {
                            $this->commandBus->handle(new RegisterProgram($user, $program, false, true));
                        }
                    }
                }
            }

            //odstraneni ucastniku, pokud se odstrani automaticke prihlasovani
            if ($originalMandatory === ProgramMandatoryType::AUTO_REGISTERED && $mandatory !== ProgramMandatoryType::AUTO_REGISTERED) {
                foreach ($block->getPrograms() as $program) {
                    foreach ($program->getAttendees() as $user) {
                        $this->commandBus->handle(new UnregisterProgram($user, $program, true));
                    }
                }
            }

            //pridani ucastniku, pokud je pridano automaticke prihlaseni
            if ($originalMandatory !== ProgramMandatoryType::AUTO_REGISTERED && $mandatory === ProgramMandatoryType::AUTO_REGISTERED) {
                $allowedUsers = $this->userRepository->findBlockAllowed($block);

                foreach ($block->getPrograms() as $program) {
                    foreach ($allowedUsers as $user) {
                        $this->commandBus->handle(new RegisterProgram($user, $program, false, true));
                    }
                }
            }
        });
    }
}