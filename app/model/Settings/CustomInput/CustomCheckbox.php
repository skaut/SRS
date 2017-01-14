<?php

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;


class CustomCheckbox extends CustomInput
{
    protected $type = CustomInput::CHECKBOX;
}