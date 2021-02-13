<?php

declare(strict_types=1);

namespace App\Model\Settings\Queries;

class SettingBoolValueQuery
{
    private string $item;

    public function __construct(string $item)
    {
        $this->item = $item;
    }

    public function getItem(): string
    {
        return $this->item;
    }
}
