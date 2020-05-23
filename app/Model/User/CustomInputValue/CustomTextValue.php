<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita hodnota vlastního textového pole přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_text_value")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomTextValue extends CustomInputValue
{
    /**
     * Hodnota textového pole přihlášky.
     *
     * @ORM\Column(type="string")
     */
    protected string $value;

    public function getValue() : string
    {
        return $this->value;
    }

    public function setValue(string $value) : void
    {
        $this->value = $value;
    }

    public function getValueText() : ?string
    {
        return $this->value;
    }
}
