<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita vlastní zaškrtávací pole přihlášky
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_checkbox')]
class CustomCheckbox extends CustomInput
{
    protected string $type = CustomInput::CHECKBOX;
}
