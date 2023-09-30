<?php

declare(strict_types=1);

namespace App\Model\Program\Queries;

use App\Model\Program\Block;

class MinBlockAllowedCapacityQuery
{
    public function __construct(private readonly Block $block)
    {
    }

    public function getBlock(): Block
    {
        return $this->block;
    }
}
