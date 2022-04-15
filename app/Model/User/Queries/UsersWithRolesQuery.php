<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

class UsersWithRolesQuery
{
    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
