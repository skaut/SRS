<?php
declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita vlastní příloha přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_file_value")
 */
class CustomFileValue extends CustomInputValue implements ICustomInputValue
{
    /**
     * Název souboru.
     * @ORM\Column(type="string", nullable=true)
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
