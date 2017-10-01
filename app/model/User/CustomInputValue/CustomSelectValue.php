<?php

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita hodnota vlastního výběrového pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_select_value")
 */
class CustomSelectValue extends CustomInputValue implements ICustomInputValue
{
    /**
     * Vybraná položka výběrového pole přihlášky.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $value;


    /**
     * @return int
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
        $this->value = $value != '' ? $value : NULL;
    }

    /**
     * Vrátí název vybrané možnosti.
     * @return mixed
     */
    public function getValueOption()
    {
        return $this->value !== NULL ? explode(', ', $this->getInput()->getOptions())[$this->value] : NULL;
    }
}
