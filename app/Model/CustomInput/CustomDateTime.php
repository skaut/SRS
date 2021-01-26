<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita vlastní pole přihlášky typu datum a čas.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_datetime")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomDateTime extends CustomInput
{
    protected string $type = CustomInput::DATETIME;
}
