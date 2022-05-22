<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingIntValue
{
    public function __construct(private string $item, private ?int $value)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }
}
