<?php

namespace App\ApiModule\DTO\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;


/**
 * Objekt pro přenos údajů o bloku do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlockDetailDTO extends Nette\Object
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
    private $name;

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
    private $lectorPhoto;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $durationHours;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $durationMinutes;

    /**
     * @JMS\Type("int")
     * @var int
     */
    private $capacity;

    /**
     * @JMS\Type("boolean")
     * @var bool
     */
    private $mandatory;

    /**
     * @JMS\Type("boolean")
     * @var bool
     */
    private $autoRegister;

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
     * @JMS\Type("int")
     * @var int
     */
    private $programsCount;

    /**
     * @JMS\Type("boolean")
     * @var bool
     */
    private $userAllowed;

    /**
     * @JMS\Type("boolean")
     * @var bool
     */
    private $userAttends;


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
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
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
    public function getLectorPhoto()
    {
        return $this->lectorPhoto;
    }

    /**
     * @param string $lectorPhoto
     */
    public function setLectorPhoto($lectorPhoto)
    {
        $this->lectorPhoto = $lectorPhoto;
    }

    /**
     * @return int
     */
    public function getDurationHours()
    {
        return $this->durationHours;
    }

    /**
     * @param int $durationHours
     */
    public function setDurationHours($durationHours)
    {
        $this->durationHours = $durationHours;
    }

    /**
     * @return int
     */
    public function getDurationMinutes()
    {
        return $this->durationMinutes;
    }

    /**
     * @param int $durationMinutes
     */
    public function setDurationMinutes($durationMinutes)
    {
        $this->durationMinutes = $durationMinutes;
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
     * @return bool
     */
    public function isAutoRegister()
    {
        return $this->autoRegister;
    }

    /**
     * @param bool $autoRegister
     */
    public function setAutoRegister($autoRegister)
    {
        $this->autoRegister = $autoRegister;
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
     * @return int
     */
    public function getProgramsCount()
    {
        return $this->programsCount;
    }

    /**
     * @param int $programsCount
     */
    public function setProgramsCount($programsCount)
    {
        $this->programsCount = $programsCount;
    }

    /**
     * @return bool
     */
    public function isUserAllowed()
    {
        return $this->userAllowed;
    }

    /**
     * @param bool $userAllowed
     */
    public function setUserAllowed($userAllowed)
    {
        $this->userAllowed = $userAllowed;
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
}
