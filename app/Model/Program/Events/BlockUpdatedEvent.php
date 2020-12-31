<?php

declare(strict_types=1);

namespace App\Model\Program\Events;

use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Structure\Subevent;

class BlockUpdatedEvent
{
    private Block $block;

    private ?Category $originalCategory;

    private Subevent $originalSubevent;

    private string $originalMandatory;

    public function __construct(Block $block, ?Category $originalCategory, Subevent $originalSubevent, string $originalMandatory)
    {
        $this->block             = $block;
        $this->originalCategory  = $originalCategory;
        $this->originalSubevent  = $originalSubevent;
        $this->originalMandatory = $originalMandatory;
    }

    public function getBlock() : Block
    {
        return $this->block;
    }

    public function getOriginalCategory() : ?Category
    {
        return $this->originalCategory;
    }

    public function getOriginalSubevent() : Subevent
    {
        return $this->originalSubevent;
    }

    public function getOriginalMandatory() : string
    {
        return $this->originalMandatory;
    }
}
