<?php

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="custom_text")
 */
class CustomText extends CustomInput
{
    protected $type = CustomInput::TEXT;
}