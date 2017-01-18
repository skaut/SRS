<?php

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="custom_checkbox")
 */
class CustomCheckbox extends CustomInput
{
    protected $type = CustomInput::CHECKBOX;
}