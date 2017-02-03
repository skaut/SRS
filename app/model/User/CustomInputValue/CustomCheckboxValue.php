<?php

namespace App\Model\User\CustomInputValue;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="custom_checkbox_value")
 */
class CustomCheckboxValue extends CustomInputValue
{
    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $value;
}