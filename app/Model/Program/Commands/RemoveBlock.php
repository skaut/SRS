<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Block;

class RemoveBlock
{
    private Block $block;

    public function __construct(Block $block)
    {
        $this->block = $block;
    }

    public function getBlock() : Block
    {
        return $this->block;
    }
}
