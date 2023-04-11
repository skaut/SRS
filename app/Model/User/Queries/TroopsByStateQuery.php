<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

class TroopsByStateQuery
{
    public function __construct(private string $troopState)
    {
    }

    public function getTroopState(): string
    {
        return $this->troopState;
    }
}
