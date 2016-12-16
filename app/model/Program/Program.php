<?php

namespace App\Model\Program;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @ORM\Entity(repositoryClass="ProgramRepository")
 * @ORM\Table(name="program")
 * @JMS\ExclusionPolicy("none")
 */
class Program
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="Block", inversedBy="programs", cascade={"persist"})
     * @var Block
     * @JMS\Type("integer")
     * @JMS\Accessor(getter="getId")
     */
    protected $block;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="programs", cascade={"persist"})
     * @var ArrayCollection
     * @JMS\Type("ArrayCollection<App\Model\User\User>")
     * @JMS\Exclude
     */
    protected $attendees;

    /**
     * @ORM\ManyToOne(targetEntity="Room", cascade={"persist"})
     * @var Room
     * @JMS\Type("Room")
     * @JMS\Exclude
     */
    protected $room;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $start;

    /**
     * @ORM\Column(type="integer")
     * @var int
     * @JMS\Type("integer")
     */
    protected $duration;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     * @JMS\Type("DateTime")
     * @JMS\Exclude
     */
    protected $timestamp;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     * @JMS\Type("boolean")
     */
    protected $mandatory = false;

    /**
     * @var \DateTime
     * @JMS\Type("DateTime")
     */
    protected $end;

    /**
     * @var string
     * @JMS\Type("string")
     */
    protected $title;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     * @JMS\Type("boolean")
     * @JMS\SerializedName("allDay")
     */
    protected $allDay = false;

    /**
     * @var bool
     * @JMS\Type("boolean")
     */
    public $attends;

    /**
     * @var array
     * @JMS\Type("array<integer>")
     */
    public $blocks;

    /**
     * @var int
     * @JMS\Type("integer")
     */
    public $attendeesCount;

    /**
     * Program constructor.
     */
    public function __construct()
    {
        $this->attendees = new \Doctrine\Common\Collections\ArrayCollection();
    }
}