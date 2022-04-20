<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita vlastní pole přihlášky typu datum
 */
#[ORM\Entity]
#[ORM\Table(name: 'custom_date')]
class CustomDate extends CustomInput
{
    protected string $type = CustomInput::DATE;
}
