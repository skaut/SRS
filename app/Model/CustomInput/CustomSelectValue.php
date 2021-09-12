<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

use function assert;

/**
 * Entita hodnota vlastního výběrového pole přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_select_value")
 */
class CustomSelectValue extends CustomInputValue
{
    /**
     * Vybraná položka výběrového pole přihlášky.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $value = null;

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * Vrátí název vybrané možnosti.
     */
    public function getValueText(): string
    {
        $input = $this->getInput();
        assert($input instanceof CustomSelect);

        return $this->value !== 0 ? $input->getSelectOptions()[$this->value] : '';
    }
}
