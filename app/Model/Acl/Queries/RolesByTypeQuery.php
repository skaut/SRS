<?php

declare(strict_types=1);

namespace App\Model\Acl\Queries;

class RolesByTypeQuery
{
    public function __construct(private string $type)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }
}
