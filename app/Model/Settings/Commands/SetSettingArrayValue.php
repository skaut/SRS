<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingArrayValue
{
    /** @param mixed[] $value */
    public function __construct(private string $item, private array|null $value)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }

    /** @return mixed[]|null */
    public function getValue(): array|null
    {
        return $this->value;
    }
}
