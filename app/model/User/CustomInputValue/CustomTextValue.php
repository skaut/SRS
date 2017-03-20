<?php

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita hodnota vlastního textového pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_text_value")
 */
class CustomTextValue extends CustomInputValue implements ICustomInputValue
{
    /**
     * Hodnota textového pole přihlášky.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $value;


    /**
     * @return string
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