<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

class ConfirmTroop
{
    public function __construct(
        private int $troop_id,
        private ?string $pairedTroopCode,
    ) {
    }

    public function getTroopId(): int
    {
        return $this->troop_id;
    }

    public function getPairedTroopCode(): ?string
    {
        return $this->pairedTroopCode;
    }
}
