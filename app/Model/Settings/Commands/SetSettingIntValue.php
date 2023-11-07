<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingIntValue
{
    public function __construct(private readonly string $item, private readonly int|null $value)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function getValue(): int|null
    {
        return $this->value;
    }
}
