<?php

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="custom_text_value")
 */
class CustomTextValue extends CustomInputValue
{
    protected $type = CustomInputValue::TEXT;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $value;
}