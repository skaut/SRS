<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

use App\Model\User\Troop;

class RemoveTroop
{
    public function __construct(private Troop $troop)
    {
    }

    public function getTroop(): Troop
    {
        return $this->troop;
    }
}
