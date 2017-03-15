<?php

namespace App\Model\Program;

use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * @ORM\Entity(repositoryClass="BlockRepository")
 * @ORM\Table(name="block")
 */
class Block
{
    use Identifier;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="Program", mappedBy="block", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     * @var ArrayCollection
     */
    protected $programs;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", cascade={"persist"})
     * @var User
     */
    protected $lector;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="blocks", cascade={"persist"})
     * @var Category
     */
    protected $category;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $mandatory = 0;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $duration;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $capacity;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $tools;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $perex;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $description;


    /**
     * Block constructor.
     */
    public function __construct()
    {
        $this->programs = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return ArrayCollection
     */
    public function getPrograms()
    {
        return $this->programs;
    }

    /**
     * @param ArrayCollection $programs
     */
    public function setPrograms($programs)
    {
        $this->programs = $programs;
    }

    public function getProgramsCount()
    {
        return $this->programs->count();
    }

    /**
     * @return User
     */
    public function getLector()
    {
        return $this->lector;
    }

    /**
     * @param User $lector
     */
    public function setLector($lector)
    {
        $this->lector = $lector;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * @return int
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param int $mandatory
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
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
     * @param User $user
     * @return bool
     */
    public function isAllowed(User $user)
    {
        if (!$this->category)
            return true;

        foreach ($user->getRoles() as $role) {
            if ($role->getRegisterableCategories()->contains($this->category))
                return true;
        }
        return false;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function isAttendee(User $user)
    {
        foreach ($this->programs as $program) {
            if ($program->isAttendee($user))
                return true;
        }
        return false;
    }
}