<?php

declare(strict_types=1);

namespace App\Model\Program\Events;

use App\Model\Program\Block;
use App\Model\Program\Category;
use App\Model\Structure\Subevent;

class BlockUpdatedEvent
{
    public function __construct(
        private readonly Block $block,
        private readonly Category|null $categoryOld,
        private readonly Subevent $subeventOld,
        private readonly string $mandatoryOld,
        private readonly int|null $capacityOld,
        private readonly bool $alternatesAllowedOld,
    ) {
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function getCategoryOld(): Category|null
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

    public function getCapacityOld(): int|null
    {
        return $this->capacityOld;
    }

    public function isAlternatesAllowedOld(): bool
    {
        return $this->alternatesAllowedOld;
    }
}
