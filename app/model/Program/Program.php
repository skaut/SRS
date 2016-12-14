<?php

namespace App\Model\Program;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="\App\Model\Program\ProgramRepository")
 * @JMS\ExclusionPolicy("none")
 */
class Program
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\Program\Block", inversedBy="programs")
     * @JMS\Type("integer")
     * @JMS\Accessor(getter="getBlockId")
     */
    protected $block;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", inversedBy="programs", cascade={"persist"})
     * @JMS\Type("ArrayCollection<App\Model\User\User>")
     * @JMS\Exclude
     */
    protected $attendees;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\Program\Room")
     * @JMS\Type("App\Model\Program\Room")
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
}