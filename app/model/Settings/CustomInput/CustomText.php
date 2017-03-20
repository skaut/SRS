<?php

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita vlastní textové pole přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_text")
 */
class CustomText extends CustomInput
{
    protected $type = CustomInput::TEXT;
}