<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita vlastní zaškrtávací pole přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_checkbox")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomCheckbox extends CustomInput
{
    /** @var string */
    protected $type = CustomInput::CHECKBOX;
}
