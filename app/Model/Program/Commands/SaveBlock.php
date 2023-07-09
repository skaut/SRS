<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Block;

class SaveBlock
{
    public function __construct(private Block $block, private Block|null $blockOld = null)
    {
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function getBlockOld(): Block|null
    {
        return $this->blockOld;
    }
}
