<?php
declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita hodnota vlastního textového pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_text_value")
 */
class CustomTextValue extends CustomInputValue
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
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param $value
     */
    public function setValue(?string $value): void
    {
        $this->value = $value;
    }
}
