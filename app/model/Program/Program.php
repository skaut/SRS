<?php

namespace App\Model\Program;

use App\ApiModule\DTO\ProgramDetailDTO;
use App\Model\User\User;
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
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $duration;

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

    public function isAttendee(User $user) {
        return $this->attendees->contains($user);
    }

    public function getCapacity() {
        if ($this->block)
            return $this->block->getCapacity();
        else
            return null;
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
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    public function getEnd($basicBlockDuration) {
        if ($this->block)
            $duration = $basicBlockDuration * $this->block->getDuration();
        else
            $duration = $basicBlockDuration * $this->duration;

        $end = clone($this->start);
        return $end->add(new \DateInterval('PT' . $duration . 'M'));
    }

    /**
     * @param $basicBlockDuration
     * @param User $user
     * @param $blocksPrograms Programy blokovane timto programem.
     * @return ProgramDetailDTO
     */
    public function convertToProgramDetailDTO($basicBlockDuration, User $user = null, $blocksPrograms = null) {
        $programDetailDTO = new ProgramDetailDTO();

        $programDetailDTO->setId($this->id);

        if ($this->block) {
            $programDetailDTO->setBlockId($this->block->getId());
            $programDetailDTO->setBlockName($this->block->getName());
            $programDetailDTO->setPerex($this->block->getPerex());
            $programDetailDTO->setDescription($this->block->getDescription());
            $programDetailDTO->setCategoryName($this->block->getCategory() ? $this->block->getCategory()->getName() : null);
            $programDetailDTO->setLectorName($this->block->getLector() ? $this->block->getLector()->getDisplayName() : null);
            $programDetailDTO->setLectorAbout($this->block->getLector() ? $this->block->getLector()->getAbout() : null);
            $programDetailDTO->setMandatory($this->block->isMandatory());
        }
        else {
            $programDetailDTO->setBlockId(null);
            $programDetailDTO->setBlockName(null);
            $programDetailDTO->setPerex(null);
            $programDetailDTO->setDescription(null);
            $programDetailDTO->setCategoryName(null);
            $programDetailDTO->setLectorName(null);
            $programDetailDTO->setLectorAbout(null);
            $programDetailDTO->setMandatory(false);
        }

        $programDetailDTO->setStart($this->start);
        $programDetailDTO->setEnd($this->getEnd($basicBlockDuration));
        $programDetailDTO->setRoomName($this->getRoom() ? $this->getRoom()->getName() : null);
        $programDetailDTO->setCapacity($this->getCapacity());
        $programDetailDTO->setAttendeesCount($this->getAttendeesCount());
        $programDetailDTO->setUserAttends($this->isAttendee($user));
        $programDetailDTO->setBlocksPrograms($blocksPrograms);

        return $programDetailDTO;
    }
}