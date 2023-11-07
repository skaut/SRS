<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\Structure\Subevent;
use App\Model\User\User;

class CheckTicket
{
    public function __construct(
        private readonly User $user,
        private readonly Subevent|null $subevent,
    ) {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSubevent(): Subevent|null
    {
        return $this->subevent;
    }
}
