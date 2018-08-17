<?php

declare(strict_types=1);

namespace App\Model\Settings\CustomInput;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita vlastní příloha přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="custom_file")
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
