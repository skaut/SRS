<?php

declare(strict_types=1);

namespace App\Model\Settings\Commands;

class SetSettingArrayValue
{
    private string $item;

    /** @var mixed[] */
    private array $value;

    public function __construct(string $item, array $value)
    {
        $this->item  = $item;
        $this->value = $value;
    }

    public function getItem(): string
    {
        return $this->item;
    }

    /**
     * @return mixed[]
     */
    public function getValue(): array
    {
        return $this->value;
    }
}
