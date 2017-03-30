<?php

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita hodnota vlastního zaškrtávacího pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_checkbox_value")
 */
class CustomCheckboxValue extends CustomInputValue implements ICustomInputValue
{
    /**
     * Hodnota zaškrtávacího pole přihlášky.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $value;


    /**
     * @return bool
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }
}
