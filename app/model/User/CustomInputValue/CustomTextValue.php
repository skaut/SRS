<?php

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="custom_text_value")
 */
class CustomTextValue extends CustomInputValue implements ICustomInputValue
{
    /**
     * @ORM\Column(type="string")
     * @var string
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