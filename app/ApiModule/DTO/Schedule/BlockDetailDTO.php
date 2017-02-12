<?php

namespace App\ApiModule\DTO\Schedule;


use Nette;
use JMS\Serializer\Annotation as JMS;

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
    private $duration;

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
     * @JMS\Type("string")
     * @var string
     */
    private $tools;

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
     * @return string
     */
    public function getTools()
    {
        return $this->tools;
    }

    /**
     * @param string $tools
     */
    public function setTools($tools)
    {
        $this->tools = $tools;
    }


}