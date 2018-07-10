<?php
declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita vlastní zaškrtávací pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_checkbox")
 */
class CustomCheckbox extends CustomInput
{
    protected $type = CustomInput::CHECKBOX;
}
