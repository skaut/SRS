<?php

declare(strict_types=1);

namespace App\Model\Structure\Queries;

use App\Model\User\User;

class SubeventsQuery
{
    private bool $explicitOnly;

    private bool $registerableNowOnly;

    private ?User $user;

    private bool $userNotRegisteredOnly;

    private bool $includeUserRegistered;

    public function __construct(
        bool $explicitOnly = false,
        bool $registerableNowOnly = false,
        ?User $user = null,
        bool $userNotRegisteredOnly = false,
        bool $includeUserRegistered = false
    ) {
        $this->explicitOnly          = $explicitOnly;
        $this->registerableNowOnly   = $registerableNowOnly;
        $this->user                  = $user;
        $this->userNotRegisteredOnly = $userNotRegisteredOnly;
        $this->includeUserRegistered = $includeUserRegistered;
    }

    public function isExplicitOnly(): bool
    {
        return $this->explicitOnly;
    }

    public function isRegisterableNowOnly(): bool
    {
        return $this->registerableNowOnly;
    }

    public function getUser(): ?User
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
