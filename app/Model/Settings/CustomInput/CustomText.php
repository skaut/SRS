<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita vlastní textové pole přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_text")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomText extends CustomInput
{
    /** @var string */
    protected $type = CustomInput::TEXT;
}
