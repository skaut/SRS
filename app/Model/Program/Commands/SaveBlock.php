<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Block;

class SaveBlock
{
    public function __construct(private Block $block, private ?Block $blockOld = null)
    {
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function getBlockOld(): ?Block
    {
        return $this->blockOld;
    }
}
