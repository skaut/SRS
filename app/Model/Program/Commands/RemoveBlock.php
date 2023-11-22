<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Block;

class RemoveBlock
{
    public function __construct(private Block $block)
    {
    }

    public function getBlock(): Block
    {
        return $this->block;
    }
}
