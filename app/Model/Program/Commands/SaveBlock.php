<?php

declare(strict_types=1);

namespace App\Model\Program\Commands;

use App\Model\Program\Block;

class SaveBlock
{
    private Block $block;

    private ?Block $blockOld;

    public function __construct(Block $block, ?Block $blockOld = null)
    {
        $this->block    = $block;
        $this->blockOld = $blockOld;
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
