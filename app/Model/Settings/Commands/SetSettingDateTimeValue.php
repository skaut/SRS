<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

use DateTimeImmutable;

class SetSettingDateTimeValue
{
    public function __construct(private string $item, private DateTimeImmutable|null $value)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function getValue(): DateTimeImmutable|null
    {
        return $this->value;
    }
}
