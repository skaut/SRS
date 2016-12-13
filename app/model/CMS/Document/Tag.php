<?php

namespace App\Model\Settings;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Tag //TODO
{
    /**
     * @ORM\Column(type="string", unique=true)
     * @ORM\Id
     */
    protected $item;

    /** @ORM\Column(type="string", nullable=true) */
    protected $value;

    /** @ORM\Column(type="string", nullable=true) */
    protected $description;
}