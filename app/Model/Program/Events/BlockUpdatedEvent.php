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

    private ?int $capacityOld;

    private bool $alternatesAllowedOld;

    public function __construct(
        Block $block,
        ?Category $categoryOld,
        Subevent $subeventOld,
        string $mandatoryOld,
        ?int $capacityOld,
        bool $alternatesAllowedOld
    ) {
        $this->block                = $block;
        $this->categoryOld          = $categoryOld;
        $this->subeventOld          = $subeventOld;
        $this->mandatoryOld         = $mandatoryOld;
        $this->capacityOld          = $capacityOld;
        $this->alternatesAllowedOld = $alternatesAllowedOld;
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

    public function getCapacityOld(): ?int
    {
        return $this->capacityOld;
    }

    public function isAlternatesAllowedOld(): bool
    {
        return $this->alternatesAllowedOld;
    }
}
