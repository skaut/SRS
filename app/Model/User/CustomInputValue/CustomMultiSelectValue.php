<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use App\Model\Settings\CustomInput\CustomSelect;
use Doctrine\ORM\Mapping as ORM;
use function explode;

/**
 * Entita hodnota vlastního výběrového pole s více možnostmi přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_multiselect_value")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomMultiSelectValue extends CustomInputValue
{
    /**
     * Vybrané položky výběrového pole s více možnostmi přihlášky.
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    protected array $value = [];

    public function getValue() : array
    {
        return $this->value;
    }

    public function setValue(array $value) : void
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

//        return $this->value !== 0 ? explode(', ', $input->getOptions())[$this->value - 1] : null; //todo
    }
}
