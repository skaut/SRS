<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

use function assert;
use function implode;

/**
 * Entita hodnota vlastního výběrového pole s více možnostmi přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_multiselect_value")
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
    public function getValue(): array
    {
        return $this->value;
    }

    /**
     * @param string[] $value
     */
    public function setValue(array $value): void
    {
        $this->value = $value;
    }

    /**
     * Vrátí název vybrané možnosti.
     */
    public function getValueText(): string
    {
        $input = $this->getInput();
        assert($input instanceof CustomMultiSelect);

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
