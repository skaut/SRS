<?php

declare(strict_types=1);

namespace App\Model\Acl\Events\Subscribers;

use App\Model\Acl\Events\CategoryChangedEvent;
use App\Model\User\Commands\UpdateUsersPrograms;
use App\Model\User\Repositories\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use eGen\MessageBus\Bus\CommandBus;

class CategoryChangedEventListener
{
    private CommandBus $commandBus;

    private UserRepository $userRepository;

    public function __construct(CommandBus $commandBus, UserRepository $userRepository)
    {
        $this->commandBus     = $commandBus;
        $this->userRepository = $userRepository;
    }

    public function __invoke(CategoryChangedEvent $event) : void
    {
        $this->commandBus->handle(new UpdateUsersPrograms(new ArrayCollection($this->userRepository->findAll())));
    }
}