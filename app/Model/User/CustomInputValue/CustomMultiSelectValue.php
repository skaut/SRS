<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use App\Model\Settings\CustomInput\CustomMultiSelect;
use Doctrine\ORM\Mapping as ORM;
use function implode;

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
     *
     * @var string[]
     */
    protected array $value = [];

    /**
     * @return string[]
     */
    public function getValue() : array
    {
        return $this->value;
    }

    /**
     * @param string[] $value
     */
    public function setValue(array $value) : void
    {
        $this->value = $value;
    }

    /**
     * Vrátí název vybrané možnosti.
     */
    public function getValueText() : string
    {
        /** @var CustomMultiSelect $input */
        $input = $this->getInput();

        if (empty($this->value)) {
            return '';
        } else {
            $selectedValues = [];
            foreach ($this->value as $value) {
                $selectedValues[] = $input->getSelectOptions()[$value];
            }

            return implode(', ', $selectedValues);
        }
    }
}
