<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

use App\Model\User\User;

class UserAllowedProgramsQuery
{
    private User $user;

    private bool $paidOnly;

    public function __construct(User $user, bool $paidOnly)
    {
        $this->user     = $user;
        $this->paidOnly = $paidOnly;
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
