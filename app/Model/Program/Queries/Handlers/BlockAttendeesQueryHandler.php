<?php

declare(strict_types=1);

namespace App\Model\Program\Queries\Handlers;

use App\Model\Program\Queries\BlockAttendeesQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class BlockAttendeesQueryHandler implements MessageHandlerInterface
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    /** @return Collection<int, User> */
    public function __invoke(BlockAttendeesQuery $query): Collection
    {
        return $this->userRepository->findBlockAttendees($query->getBlock());
    }
}
