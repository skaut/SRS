<?php

declare(strict_types=1);

namespace App\Model\User\Commands;

class DeletePatrol
{
    public function __construct(
        public int $id,
    ) {
    }
}
