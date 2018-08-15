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
class CustomFileValue extends CustomInputValue
{
    /**
     * Název souboru.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $value;


    public function getValue() : ?string
    {
        return $this->value;
    }

    public function setValue(?string $value) : void
    {
        $this->value = $value;
    }
}
