<?php

namespace App\ApiModule\DTO;


use Nette;
use JMS\Serializer\Annotation as JMS;

class ProgramDetailDTO extends Nette\Object
{
    /**
     * @JMS\Type("id")
     * @var int
     */
    private $id;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $blockId;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $blockName;

    /**
     * @JMS\Type("DateTime")
     * @var \DateTime
     */
    private $start;

    /**
     * @JMS\Type("DateTime")
     * @var \DateTime
     */
    private $end;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $perex;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $description;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $roomName;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $categoryName;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $lectorName;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $lectorAbout;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $attendeesCount;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $capacity;

    /**
     * @JMS\Type("bool")
     * @var bool
     */
    private $userAttends;

    /**
     * @JMS\Type("bool")
     * @var bool
     */
    private $mandatory;

    /**
     * @JMS\Type("array<int>")
     * @var array
     */
    private $blocksPrograms;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getBlockId()
    {
        return $this->blockId;
    }

    /**
     * @param int $blockId
     */
    public function setBlockId($blockId)
    {
        $this->blockId = $blockId;
    }

    /**
     * @return string
     */
    public function getBlockName()
    {
        return $this->blockName;
    }

    /**
     * @param string $blockName
     */
    public function setBlockName($blockName)
    {
        $this->blockName = $blockName;
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
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param \DateTime $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * @return string
     */
    public function getPerex()
    {
        return $this->perex;
    }

    /**
     * @param string $perex
     */
    public function setPerex($perex)
    {
        $this->perex = $perex;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getRoomName()
    {
        return $this->roomName;
    }

    /**
     * @param string $roomName
     */
    public function setRoomName($roomName)
    {
        $this->roomName = $roomName;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * @param string $categoryName
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;
    }

    /**
     * @return string
     */
    public function getLectorName()
    {
        return $this->lectorName;
    }

    /**
     * @param string $lectorName
     */
    public function setLectorName($lectorName)
    {
        $this->lectorName = $lectorName;
    }

    /**
     * @return string
     */
    public function getLectorAbout()
    {
        return $this->lectorAbout;
    }

    /**
     * @param string $lectorAbout
     */
    public function setLectorAbout($lectorAbout)
    {
        $this->lectorAbout = $lectorAbout;
    }

    /**
     * @return int
     */
    public function getAttendeesCount()
    {
        return $this->attendeesCount;
    }

    /**
     * @param int $attendeesCount
     */
    public function setAttendeesCount($attendeesCount)
    {
        $this->attendeesCount = $attendeesCount;
    }

    /**
     * @return int
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @param int $capacity
     */
    public function setCapacity($capacity)
    {
        $this->capacity = $capacity;
    }

    /**
     * @return bool
     */
    public function isUserAttends()
    {
        return $this->userAttends;
    }

    /**
     * @param bool $userAttends
     */
    public function setUserAttends($userAttends)
    {
        $this->userAttends = $userAttends;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param bool $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    /**
     * @return array
     */
    public function getBlocksPrograms()
    {
        return $this->blocksPrograms;
    }

    /**
     * @param array $blocksPrograms
     */
    public function setBlocksPrograms($blocksPrograms)
    {
        $this->blocksPrograms = $blocksPrograms;
    }


}