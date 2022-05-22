<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

use DateTimeImmutable;

class SetSettingDateTimeValue
{
    public function __construct(private string $item, private ?DateTimeImmutable $value)
    {
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
