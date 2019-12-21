<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use App\Model\Settings\CustomInput\CustomSelect;
use Doctrine\ORM\Mapping as ORM;
use function explode;

/**
 * Entita hodnota vlastního výběrového pole přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_select_value")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomSelectValue extends CustomInputValue
{
    /**
     * Vybraná položka výběrového pole přihlášky.
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var int
     */
    protected $value;

    public function getValue() : ?int
    {
        return $this->value;
    }

    public function setValue(?int $value) : void
    {
        $this->value = $value;
    }

    /**
     * Vrátí název vybrané možnosti.
     */
    public function getValueOption() : ?string
    {
        /** @var CustomSelect $input */
        $input = $this->getInput();

        return $this->value !== 0 ? explode(', ', $input->getOptions())[$this->value - 1] : null;
    }
}
