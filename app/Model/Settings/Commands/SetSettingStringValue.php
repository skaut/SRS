<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingStringValue
{
    private string $item;

    private ?string $value;

    public function __construct(string $item, ?string $value)
    {
        $this->item  = $item;
        $this->value = $value;
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
