<?php

declare(strict_types=1);

namespace App\Model\Acl\Events\Subscribers;

use App\Model\Acl\Events\CategoryUpdatedEvent;
use App\Model\User\Commands\UpdateUsersPrograms;
use App\Model\User\Repositories\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\CommandBus;

class CategoryUpdatedEventListener
{
    private CommandBus $commandBus;

    private UserRepository $userRepository;

    public function __construct(CommandBus $commandBus, UserRepository $userRepository)
    {
        $this->commandBus     = $commandBus;
        $this->userRepository = $userRepository;
    }

    /**
     * Pokud se změnily registrovatelné role u kategorie, je třeba přihlásit/odhlásit programy účastníků.
     */
    public function __invoke(CategoryUpdatedEvent $event) : void
    {
        $registerableRoles  = $event->getCategory()->getRegisterableRoles()->toArray();
        $originalRegisterableRoles = $event->getOriginalRegisterableRoles()->toArray();

        if (! empty(array_diff($registerableRoles, $originalRegisterableRoles))) {
            $this->commandBus->handle(new UpdateUsersPrograms(new ArrayCollection($this->userRepository->findAll())));
        }
    }
}