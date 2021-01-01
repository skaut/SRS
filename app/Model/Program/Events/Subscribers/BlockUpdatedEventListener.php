<?php

declare(strict_types=1);

namespace App\Model\Program\Events\Subscribers;

use App\Model\Enums\ProgramMandatoryType;
use App\Model\Program\Events\BlockUpdatedEvent;
use App\Model\Program\Queries\ProgramAttendeesQuery;
use App\Model\User\Commands\RegisterProgram;
use App\Model\User\Commands\UnregisterProgram;
use App\Model\User\Repositories\UserRepository;
use eGen\MessageBus\Bus\CommandBus;
use eGen\MessageBus\Bus\QueryBus;
use Nettrine\ORM\EntityManagerDecorator;

class BlockUpdatedEventListener
{
    private CommandBus $commandBus;

    private QueryBus $queryBus;

    private EntityManagerDecorator $em;

    private UserRepository $userRepository;

    public function __construct(
        CommandBus $commandBus,
        QueryBus $queryBus,
        EntityManagerDecorator $em,
        UserRepository $userRepository
    ) {
        $this->commandBus     = $commandBus;
        $this->queryBus       = $queryBus;
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

            $allowedUsers = $this->userRepository->findBlockAllowed($block);

            //aktualizace ucastniku pri zmene kategorie nebo podakce
            if (($category === null && $originalCategory !== null)
                || ($category !== null && $originalCategory === null)
                || ($category !== null && $originalCategory !== null && $category->getId() !== $originalCategory->getId())
                || ($subevent->getId() !== $originalSubevent->getId())) {

                foreach ($block->getPrograms() as $program) {
                    $programAttendees = $this->queryBus->handle(new ProgramAttendeesQuery($program));
                    foreach ($programAttendees as $user) {
                        if (! $allowedUsers->contains($user)) {
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
                    $programAttendees = $this->queryBus->handle(new ProgramAttendeesQuery($program));
                    foreach ($programAttendees as $user) {
                        $this->commandBus->handle(new UnregisterProgram($user, $program, true));
                    }
                }
            }

            //pridani ucastniku, pokud je pridano automaticke prihlaseni
            if ($originalMandatory !== ProgramMandatoryType::AUTO_REGISTERED && $mandatory === ProgramMandatoryType::AUTO_REGISTERED) {
                foreach ($block->getPrograms() as $program) {
                    foreach ($allowedUsers as $user) {
                        $this->commandBus->handle(new RegisterProgram($user, $program, false, true));
                    }
                }
            }
        });
    }
}
