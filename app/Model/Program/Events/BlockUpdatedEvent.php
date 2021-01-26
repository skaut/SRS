<?php

declare(strict_types=1);

namespace App\Model\Program\Events;

use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Structure\Subevent;

class BlockUpdatedEvent
{
    private Block $block;

    private ?Category $categoryOld;

    private Subevent $subeventOld;

    private string $mandatoryOld;

    public function __construct(Block $block, ?Category $categoryOld, Subevent $subeventOld, string $mandatoryOld)
    {
        $this->block        = $block;
        $this->categoryOld  = $categoryOld;
        $this->subeventOld  = $subeventOld;
        $this->mandatoryOld = $mandatoryOld;
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function getCategoryOld(): ?Category
    {
        return $this->categoryOld;
    }

    public function getSubeventOld(): Subevent
    {
        return $this->subeventOld;
    }

    public function getMandatoryOld(): string
    {
        return $this->mandatoryOld;
    }
}
