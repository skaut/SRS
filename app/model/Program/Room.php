<?php

namespace App\Model\Program;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="room")
 */
class Room
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\OneToMany(targetEntity="Program", mappedBy="room", cascade={"persist", "remove"})
     * @JMS\Type("ArrayCollection<Program>")
     * @JMS\Exclude
     */
    protected $blocks;

    /** @ORM\Column(type="string", unique=true) */
    protected $name;


    public function __construct()
    {
        $this->blocks = new \Doctrine\Common\Collections\ArrayCollection();
    }
}