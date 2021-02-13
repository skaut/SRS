<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

use DateTimeImmutable;

class SetSettingDateTimeValue
{
    private string $item;

    private ?DateTimeImmutable $value;

    public function __construct(string $item, ?DateTimeImmutable $value)
    {
        $this->item  = $item;
        $this->value = $value;
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function getValue(): ?DateTimeImmutable
    {
        return $this->value;
    }
}
