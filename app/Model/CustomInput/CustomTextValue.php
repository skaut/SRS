<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita hodnota vlastního textového pole přihlášky.
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_text_value')]
class CustomTextValue extends CustomInputValue
{
    /**
     * Hodnota textového pole přihlášky.
     */
    #[ORM\Column(type: 'text')]
    protected string|null $value = null;

    public function getValue(): string|null
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getValueText(): string
    {
        return $this->value ?: '';
    }
}
