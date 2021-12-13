<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\Structure\Subevent;
use App\Model\User\User;

class CheckTicket
{
    private User $user;

    private ?Subevent $subevent;

    public function __construct(User $user, ?Subevent $subevent)
    {
        $this->user     = $user;
        $this->subevent = $subevent;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSubevent(): ?Subevent
    {
        return $this->subevent;
    }
}
