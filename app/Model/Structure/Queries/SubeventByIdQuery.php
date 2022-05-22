<?php

declare(strict_types=1);

namespace App\Model\Structure\Queries;

class SubeventByIdQuery
{
    public function __construct(private int $id)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }
}
