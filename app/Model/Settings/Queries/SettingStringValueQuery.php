<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries;

class SettingStringValueQuery
{
    public function __construct(private string $item)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }
}
