<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingStringValue
{
    public function __construct(private string $item, private ?string $value)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
