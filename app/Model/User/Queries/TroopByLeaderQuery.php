<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

class TroopByLeaderQuery
{
    public function __construct(private int $leaderId)
    {
    }

    public function getLeaderId(): int
    {
        return $this->leaderId;
    }
}
