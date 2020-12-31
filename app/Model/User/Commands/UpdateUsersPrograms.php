<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\User\User;
use Doctrine\Common\Collections\Collection;

class UpdateUsersPrograms
{
    /** @var Collection<User> */
    private Collection $users;

    /**
     * @param Collection<User> $users
     */
    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    /**
     * @return Collection<User>
     */
    public function getUsers() : Collection
    {
        return $this->users;
    }
}
