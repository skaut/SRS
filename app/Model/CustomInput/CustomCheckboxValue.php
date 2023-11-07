<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita hodnota vlastního zaškrtávacího pole přihlášky.
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_checkbox_value')]
class CustomCheckboxValue extends CustomInputValue
{
    /**
     * Hodnota zaškrtávacího pole přihlášky.
     */
    #[ORM\Column(type: 'boolean')]
    protected bool|null $value = null;

    public function getValue(): bool|null
    {
        return $this->value;
    }

    public function setValue(bool $value): void
    {
        $this->value = $value;
    }

    public function getValueText(): string
    {
        return $this->value ? (string) $this->value : '';
    }
}
