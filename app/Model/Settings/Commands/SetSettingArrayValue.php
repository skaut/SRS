<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingArrayValue
{
    /** @param string[]|null $value */
    public function __construct(private readonly string $item, private readonly array|null $value)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }

    /** @return string[]|null */
    public function getValue(): array|null
    {
        return $this->value;
    }
}
