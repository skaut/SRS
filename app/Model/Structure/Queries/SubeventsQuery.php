<?php

declare(strict_types=1);

namespace App\Model\Structure\Queries;

use App\Model\User\User;

class SubeventsQuery
{
    public function __construct(
        private bool $explicitOnly = false,
        private bool $registerableNowOnly = false,
        private User|null $user = null,
        private bool $userNotRegisteredOnly = false,
        private bool $includeUserRegistered = false,
    ) {
    }

    public function isExplicitOnly(): bool
    {
        return $this->explicitOnly;
    }

    public function isRegisterableNowOnly(): bool
    {
        return $this->registerableNowOnly;
    }

    public function getUser(): User|null
    {
        return $this->user;
    }

    public function isUserNotRegisteredOnly(): bool
    {
        return $this->userNotRegisteredOnly;
    }

    public function isIncludeUserRegistered(): bool
    {
        return $this->includeUserRegistered;
    }
}
