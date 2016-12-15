<?php

namespace App\Model\Program;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ProgramRepository")
 * @ORM\Table(name="program")
 * @JMS\ExclusionPolicy("none")
 */
class Program
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="Block", inversedBy="programs" cascade={"persist", "remove"})
     * @JMS\Type("integer")
     * @JMS\Accessor(getter="getBlockId")
     */
    protected $block;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="programs", cascade={"persist", "remove"})
     * @JMS\Type("ArrayCollection<App\Model\User\User>")
     * @JMS\Exclude
     */
    protected $attendees;

    /**
     * @ORM\ManyToOne(targetEntity="Room" inversedBy="room", cascade={"persist", "remove"})
     * @JMS\Type("Room")
     * @JMS\Exclude
     */
    protected $room;

    /**
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     */
    protected $start;

    /**
     * @ORM\Column(type="integer")
     * @JMS\Type("integer")
     */
    protected $duration;

    /**
     * @ORM\Column(type="datetime")
     * @JMS\Type("DateTime")
     * @JMS\Exclude
     */
    protected $timestamp;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     */
    protected $mandatory = false;

    /**
     * @JMS\Type("DateTime")
     */
    protected $end;

    /**
     * @JMS\Type("string")
     */
    protected $title;

    /**
     * @ORM\Column(type="boolean")
     * @JMS\Type("boolean")
     * @JMS\SerializedName("allDay")
     */
    protected $allDay = false;

    /**
     * @JMS\Type("boolean")
     */
    public $attends;

    /**
     * @JMS\Type("array<integer>")
     */
    public $blocks;

    /**
     * @JMS\Type("integer")
     */
    public $attendeesCount;

    public function __construct()
    {
        $this->attendees = new \Doctrine\Common\Collections\ArrayCollection();
    }
}