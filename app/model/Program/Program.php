<?php

namespace App\Model\Program;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity(repositoryClass="ProgramRepository")
 * @ORM\Table(name="program")
 */
class Program
{
    use Identifier;

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
     * Program constructor.
     */
    public function __construct()
    {
        $this->attendees = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Block
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @param Block $block
     */
    public function setBlock($block)
    {
        $this->block = $block;
    }

    /**
     * @return ArrayCollection
     */
    public function getAttendees()
    {
        return $this->attendees;
    }

// TODO nefunguje
//    /**
//     * @param ArrayCollection $attendees
//     */
//    public function setAttendees($attendees)
//    {
//        $this->attendees = $attendees;
//    }

    public function getAttendeesCount() {
        return $this->attendees->count();
    }

    public function getOccupancy() {
        if ($this->block->getCapacity() !== null)
            return $this->getAttendeesCount() . '/' . $this->block->getCapacity();
        else
            return $this->getAttendeesCount();
    }

    /**
     * @return Room
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * @param Room $room
     */
    public function setRoom($room)
    {
        $this->room = $room;
    }

    /**
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param \DateTime $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    public function getEnd($basicBlockDuration) {
        $end = clone($this->start);
        return $end->add(new \DateInterval('PT' . $basicBlockDuration * $this->block->getDuration() . 'M'));
    }
}