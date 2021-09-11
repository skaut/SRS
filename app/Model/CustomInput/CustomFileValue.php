<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita vlastní příloha přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_file_value")
 */
class CustomFileValue extends CustomInputValue
{
    /**
     * Název souboru.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $value = null;

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): void
    {
        $this->value = $value;
    }

    public function getValueText(): string
    {
        return '';
    }
}
