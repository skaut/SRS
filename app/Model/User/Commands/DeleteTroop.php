<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

class DeleteTroop
{
    public function __construct(
        public int $id,
    ) {
    }
}
