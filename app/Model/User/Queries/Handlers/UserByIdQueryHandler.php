<?php

declare(strict_types=1);

namespace App\Model\User\Queries\Handlers;

use App\Model\User\Queries\UserByIdQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class UserByIdQueryHandler implements MessageHandlerInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function __invoke(UserByIdQuery $query): ?User
    {
        return $this->userRepository->findById($query->getId());
    }
}
