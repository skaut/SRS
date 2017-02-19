<?php

namespace App\ApiModule\DTO\Schedule;


use Nette;
use JMS\Serializer\Annotation as JMS;

class ProgramWebDetailDTO extends Nette\Object
{
    /**
     * @JMS\Type("int")
     * @var int
     */
    private $id;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $title;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $start;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $end;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $color;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $category;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $lector;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $aboutLector;

    /**
     * @JMS\Type("string")
     * @var string
     */
    private $room;

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
     * @JMS\Type("boolean")
     * @var bool
     */
    private $userAttends;

    /**
     * @JMS\Type("boolean")
     * @var bool
     */
    private $blocked;

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
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param string $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return string
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @param string $end
     */
    public function setEnd($end)
    {
        $this->end = $end;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return string
     */
    public function getLector()
    {
        return $this->lector;
    }

    /**
     * @param string $lector
     */
    public function setLector($lector)
    {
        $this->lector = $lector;
    }

    /**
     * @return string
     */
    public function getAboutLector()
    {
        return $this->aboutLector;
    }

    /**
     * @param string $aboutLector
     */
    public function setAboutLector($aboutLector)
    {
        $this->aboutLector = $aboutLector;
    }

    /**
     * @return string
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * @param string $room
     */
    public function setRoom($room)
    {
        $this->room = $room;
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
    public function isBlocked()
    {
        return $this->blocked;
    }

    /**
     * @param bool $blocked
     */
    public function setBlocked($blocked)
    {
        $this->blocked = $blocked;
    }
}