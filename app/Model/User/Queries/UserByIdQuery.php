<?php

declare(strict_types=1);

namespace App\Model\User\Queries;

class UserByIdQuery
{
    public function __construct(private readonly int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
