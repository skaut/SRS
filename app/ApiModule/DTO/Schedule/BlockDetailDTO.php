<?php

declare(strict_types=1);

namespace App\ApiModule\DTO\Schedule;

use JMS\Serializer\Annotation as JMS;
use Nette;

/**
 * Objekt pro přenos údajů o bloku do FullCalendar.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlockDetailDTO
{
    use Nette\SmartObject;

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


    public function getId() : int
    {
        return $this->id;
    }

    public function setId(int $id) : void
    {
        $this->id = $id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getCategory() : string
    {
        return $this->category;
    }

    public function setCategory(string $category) : void
    {
        $this->category = $category;
    }

    public function getLector() : string
    {
        return $this->lector;
    }

    public function setLector(string $lector) : void
    {
        $this->lector = $lector;
    }

    public function getAboutLector() : string
    {
        return $this->aboutLector;
    }

    public function setAboutLector(string $aboutLector) : void
    {
        $this->aboutLector = $aboutLector;
    }

    public function getLectorPhoto() : string
    {
        return $this->lectorPhoto;
    }

    public function setLectorPhoto(string $lectorPhoto) : void
    {
        $this->lectorPhoto = $lectorPhoto;
    }

    public function getDurationHours() : int
    {
        return $this->durationHours;
    }

    public function setDurationHours(int $durationHours) : void
    {
        $this->durationHours = $durationHours;
    }

    public function getDurationMinutes() : int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes) : void
    {
        $this->durationMinutes = $durationMinutes;
    }

    public function getCapacity() : int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity) : void
    {
        $this->capacity = $capacity;
    }

    public function isMandatory() : bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory) : void
    {
        $this->mandatory = $mandatory;
    }

    public function isAutoRegister() : bool
    {
        return $this->autoRegister;
    }

    public function setAutoRegister(bool $autoRegister) : void
    {
        $this->autoRegister = $autoRegister;
    }

    public function getPerex() : string
    {
        return $this->perex;
    }

    public function setPerex(string $perex) : void
    {
        $this->perex = $perex;
    }

    public function getDescription() : string
    {
        return $this->description;
    }

    public function setDescription(string $description) : void
    {
        $this->description = $description;
    }

    public function getProgramsCount() : int
    {
        return $this->programsCount;
    }

    public function setProgramsCount(int $programsCount) : void
    {
        $this->programsCount = $programsCount;
    }

    public function isUserAllowed() : bool
    {
        return $this->userAllowed;
    }

    public function setUserAllowed(bool $userAllowed) : void
    {
        $this->userAllowed = $userAllowed;
    }

    public function isUserAttends() : bool
    {
        return $this->userAttends;
    }

    public function setUserAttends(bool $userAttends) : void
    {
        $this->userAttends = $userAttends;
    }
}
