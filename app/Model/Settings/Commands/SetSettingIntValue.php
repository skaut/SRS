<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingIntValue
{
    private string $item;

    private ?int $value;

    public function __construct(string $item, ?int $value)
    {
        $this->item  = $item;
        $this->value = $value;
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
