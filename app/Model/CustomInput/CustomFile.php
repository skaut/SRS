<?php

declare(strict_types=1);

namespace App\Model\CustomInput;

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
    public const PATH = 'custom_input';

    protected string $type = CustomInput::FILE;
}
