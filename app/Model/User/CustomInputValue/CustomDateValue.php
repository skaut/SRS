<?php

declare(strict_types=1);

namespace App\Model\User\CustomInputValue;

use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita hodnota vlastního pole přihlášky typu datum.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_date_value")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomDateValue extends CustomInputValue
{
    /**
     * Hodnota pole přihlášky typu datum.
     *
     * @ORM\Column(type="date_immutable", nullable=true)
     */
    protected ?DateTimeImmutable $value = null;

    public function getValue() : ?DateTimeImmutable
    {
        return $this->value;
    }

    public function setValue(?DateTimeImmutable $value) : void
    {
        $this->value = $value;
    }

    public function getValueText() : string
    {
        return $this->value ? $this->value->format(Helpers::DATE_FORMAT) : '';
    }
}
