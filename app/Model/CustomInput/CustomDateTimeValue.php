<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita hodnota vlastního pole přihlášky typu datum a čas.
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_datetime_value')]
class CustomDateTimeValue extends CustomInputValue
{
    /**
     * Hodnota pole přihlášky typu datum a čas.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    protected DateTimeImmutable|null $value = null;

    public function getValue(): DateTimeImmutable|null
    {
        return $this->value;
    }

    public function setValue(DateTimeImmutable|null $value): void
    {
        $this->value = $value;
    }

    public function getValueText(): string
    {
        return $this->value ? $this->value->format(Helpers::DATETIME_FORMAT) : '';
    }
}
