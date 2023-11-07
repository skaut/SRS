<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

use App\Model\Structure\Subevent;
use App\Model\User\User;

class TicketChecksByUserAndSubeventQuery
{
    public function __construct(private readonly User $user, private readonly Subevent $subevent)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSubevent(): Subevent
    {
        return $this->subevent;
    }
}
