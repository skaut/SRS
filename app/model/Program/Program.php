<?php

namespace App\Model\Program;

use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita program.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="ProgramRepository")
 * @ORM\Table(name="program")
 */
class Program
{
    use Identifier;

    /**
     * Programový blok.
     * @ORM\ManyToOne(targetEntity="Block", inversedBy="programs", cascade={"persist"})
     * @var Block
     */
    protected $block;

    /**
     * Účastníci programu.
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="programs", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $attendees;

    /**
     * Místnost.
     * @ORM\ManyToOne(targetEntity="Room", inversedBy="programs", cascade={"persist"})
     * @var Room
     */
    protected $room;

    /**
     * Začátek programu.
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

    /**
     * @param ArrayCollection $attendees
     */
    public function setAttendees($attendees)
    {
        $this->removeAllAttendees();
        foreach ($attendees as $attendee) {
            $this->addAttendee($attendee);
        }
    }

    /**
     * @param $user
     */
    public function addAttendee($user)
    {
        if (!$this->attendees->contains($user)) {
            $this->attendees->add($user);
            $user->addProgram($this);
        }
    }

    /**
     * Vrací počet účastníků.
     * @return int
     */
    public function getAttendeesCount()
    {
        return $this->attendees->count();
    }

    /**
     * Odstraní všechny účastníky programu.
     */
    public function removeAllAttendees()
    {
        foreach ($this->attendees as $attendee) {
            $attendee->removeProgram($this);
        }
    }

    /**
     * Je uživatel účastník programu?
     * @param User $user
     * @return bool
     */
    public function isAttendee(User $user)
    {
        return $this->attendees->contains($user);
    }

    /**
     * Vrací kapacitu programového bloku.
     * @return mixed
     */
    public function getCapacity()
    {
        return $this->block->getCapacity();
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

    /**
     * Vrací konec programu vypočtený podle délky bloku.
     * @return \DateTime
     */
    public function getEnd()
    {
        $end = clone($this->start);
        $end->add(new \DateInterval('PT' . $this->block->getDuration() . 'M'));
        return $end;
    }
}