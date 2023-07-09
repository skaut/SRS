<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingBoolValue
{
    public function __construct(private string $item, private bool|null $value)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function getValue(): bool|null
    {
        return $this->value;
    }
}
