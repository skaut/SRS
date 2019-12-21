<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita vlastní příloha přihlášky.
 *
 * @ORM\Entity
 * @ORM\Table(name="custom_file")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomFile extends CustomInput
{
    /**
     * Adresář pro ukládání souborů.
     */
    public const PATH = '/custom_input';

    /** @var string */
    protected $type = CustomInput::FILE;
}
