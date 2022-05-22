<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

class UserByIdQuery
{
    public function __construct(private int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
