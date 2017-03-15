<?php

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="custom_checkbox_value")
 */
class CustomCheckboxValue extends CustomInputValue implements ICustomInputValue
{
    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $value;


    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}