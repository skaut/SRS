<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingBoolValue
{
    private string $item;

    private ?bool $value;

    public function __construct(string $item, ?bool $value)
    {
        $this->item  = $item;
        $this->value = $value;
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function getValue(): ?bool
    {
        return $this->value;
    }
}
