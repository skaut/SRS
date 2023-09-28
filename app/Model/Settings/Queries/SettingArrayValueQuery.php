<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries;

class SettingArrayValueQuery
{
    public function __construct(private readonly string $item)
    {
    }

    public function getItem(): string
    {
        return $this->item;
    }
}
