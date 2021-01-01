<?php

declare(strict_types=1);

namespace App\Model\Program\Queries\Handlers;

use App\Model\Program\Queries\BlockAttendeesQuery;
use App\Model\User\Repositories\UserRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class BlockAttendeesQueryHandler
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @return Collection<User>
     */
    public function __invoke(BlockAttendeesQuery $query) : Collection
    {
        return $this->userRepository->findBlockAttendees($query->getBlock());
    }
}
