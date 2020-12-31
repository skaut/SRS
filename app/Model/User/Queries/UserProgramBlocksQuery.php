<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

use App\Model\User\User;

class UserProgramBlocksQuery
{
    private User $user;

    private bool $includeAlternates;

    public function __construct(User $user, bool $includeAlternates = false)
    {
        $this->user              = $user;
        $this->includeAlternates = $includeAlternates;
    }

    public function getUser() : User
    {
        return $this->user;
    }

    public function isIncludeAlternates() : bool
    {
        return $this->includeAlternates;
    }
}
