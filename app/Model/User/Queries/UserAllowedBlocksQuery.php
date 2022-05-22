<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

use App\Model\User\User;

class UserAllowedBlocksQuery
{
    public function __construct(private User $user, private bool $paidOnly)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function isPaidOnly(): bool
    {
        return $this->paidOnly;
    }
}
