<?php

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;


class CustomText extends CustomInput
{
    protected $type = CustomInput::TEXT;
}