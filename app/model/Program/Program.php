<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @ORM\ManyToOne(targetEntity="Block", inversedBy="programs")
     * @var Block
     */
    protected $block;

    /**
     * Účastníci programu.
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", mappedBy="programs", cascade={"persist"})
     * @var Collection|User[]
     */
    protected $attendees;

    /**
     * Obsazenost.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $occupancy = 0;

    /**
     * Místnost.
     * @ORM\ManyToOne(targetEntity="Room", inversedBy="programs")
     * @var Room
     */
    protected $room;

    /**
     * Začátek programu.
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    protected $start;


    public function __construct(Block $block)
    {
        $this->block     = $block;
        $this->attendees = new ArrayCollection();
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getBlock() : Block
    {
        return $this->block;
    }

    /**
     * @return Collection|User[]
     */
    public function getAttendees() : Collection
    {
        return $this->attendees;
    }

    /**
     * Vrací počet účastníků.
     */
    public function getAttendeesCount() : int
    {
        return $this->attendees->count();
    }

    /**
     * Je uživatel účastník programu?
     */
    public function isAttendee(User $user) : bool
    {
        return $this->attendees->contains($user);
    }

    /**
     * Vrací kapacitu programového bloku.
     */
    public function getCapacity() : ?int
    {
        return $this->block->getCapacity();
    }

    public function getOccupancy() : int
    {
        return $this->occupancy;
    }

    public function getRoom() : ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room) : void
    {
        $this->room = $room;
    }

    public function getStart() : \DateTime
    {
        return $this->start;
    }

    public function setStart(\DateTime $start) : void
    {
        $this->start = $start;
    }

    /**
     * Vrací konec programu vypočtený podle délky bloku.
     * @throws \Exception
     */
    public function getEnd() : \DateTime
    {
        $end = clone($this->start);
        $end->add(new \DateInterval('PT' . $this->block->getDuration() . 'M'));
        return $end;
    }
}
