<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use App\Model\Settings\CustomInput\CustomMultiSelect;
use App\Model\Settings\CustomInput\CustomSelect;
use Doctrine\ORM\Mapping as ORM;
use Mpdf\Tag\P;
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
    public function getValueText() : ?string
    {
        /** @var CustomMultiSelect $input */
        $input = $this->getInput();

        if (empty($this->value)) {
            return null;
        } else {
            $selectedValues = [];
            foreach ($this->value as $value) {
                $selectedValues[] = $input->getSelectOptions()[$value];
            }
            return implode(", ", $selectedValues);
        }
    }
}
