<?php

declare(strict_types=1);

namespace App\Model\User\Commands\Handlers;

use App\Model\User\Commands\SaveUser;
use App\Model\User\Repositories\UserRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SaveUserHandler implements MessageHandlerInterface
{
    private UserRepository $userRepository;

    public function __construct(
        UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    public function __invoke(SaveUser $command): void
    {
        $this->userRepository->save($command->getUser());
    }
}
