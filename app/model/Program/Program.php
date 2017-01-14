<?php

namespace App\Model\Program;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ProgramRepository")
 * @ORM\Table(name="program")
 */
class Program
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="Block", inversedBy="programs", cascade={"persist"})
     * @var Block
     */
    protected $block;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="programs", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $attendees;

    /**
     * @ORM\ManyToOne(targetEntity="Room", cascade={"persist"})
     * @var Room
     */
    protected $room;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $start;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $duration;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $mandatory = false;

    /**
     * Program constructor.
     */
    public function __construct()
    {
        $this->attendees = new \Doctrine\Common\Collections\ArrayCollection();
    }
}