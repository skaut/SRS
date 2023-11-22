<?php

declare(strict_types=1);

namespace App\Model\Program\Queries;

use App\Model\Program\Block;

class BlockAttendeesQuery
{
    public function __construct(private Block $block)
    {
    }

    public function getBlock(): Block
    {
        return $this->block;
    }
}
