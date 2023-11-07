<?php

declare(strict_types=1);

namespace App\Model\Structure\Queries;

use App\Model\User\User;

class SubeventsQuery
{
    public function __construct(
        private readonly bool $explicitOnly = false,
        private readonly bool $registerableNowOnly = false,
        private readonly User|null $user = null,
        private readonly bool $userNotRegisteredOnly = false,
        private readonly bool $includeUserRegistered = false,
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
