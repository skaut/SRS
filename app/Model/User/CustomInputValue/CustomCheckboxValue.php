<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita hodnota vlastního zaškrtávacího pole přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_checkbox_value")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomCheckboxValue extends CustomInputValue
{
    /**
     * Hodnota zaškrtávacího pole přihlášky.
     *
     * @ORM\Column(type="boolean")
     */
    protected ?bool $value = null;

    public function getValue() : ?bool
    {
        return $this->value;
    }

    public function setValue(?bool $value) : void
    {
        $this->value = $value;
    }
}
