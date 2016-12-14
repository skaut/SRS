<?php

namespace App\Model\Program;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Room
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\OneToMany(targetEntity="\App\Model\Program\Program", mappedBy="room", cascade={"persist"}, onDelete="SET NULL")
     * @JMS\Type("ArrayCollection<App\Model\Program\Program>")
     * @JMS\Exclude
     */
    protected $blocks;

    /** @ORM\Column(type="string", unique=true) */
    protected $name;
}