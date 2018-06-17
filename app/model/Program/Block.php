<?php
declare(strict_types=1);

namespace App\Model\Program;

use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita programový blok.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="BlockRepository")
 * @ORM\Table(name="block")
 */
class Block
{
    use Identifier;

    /**
     * Název programového bloku.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * Programy v bloku.
     * @ORM\OneToMany(targetEntity="Program", mappedBy="block", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     * @var Collection
     */
    protected $programs;

    /**
     * Lektor.
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", inversedBy="lecturersBlocks")
     * @var User
     */
    protected $lector;

    /**
     * Kategorie bloku.
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="blocks")
     * @var Category
     */
    protected $category;

    /**
     * Podakce bloku.
     * @ORM\ManyToOne(targetEntity="\App\Model\Structure\Subevent", inversedBy="blocks")
     * @var Subevent
     */
    protected $subevent;

    /**
     * Povinnost. 0 - nepovinný, 1 - povinný, 2 - automaticky zapisovaný.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $mandatory = 0;

    /**
     * Délka programového bloku.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $duration;

    /**
     * Kapacita.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $capacity;

    /**
     * Pomůcky.
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $tools;

    /**
     * Stručný popis.
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $perex;

    /**
     * Podrobný popis.
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
     * @return Collection
     */
    public function getPrograms()
    {
        return $this->programs;
    }

    /**
     * Vrací seznam účastníků bloku.
     * @return Collection
     */
    public function getAttendees()
    {
        $attendees = new ArrayCollection();
        foreach ($this->programs as $program) {
            foreach ($program->getAttendees() as $attendee) {
                $attendees->add($attendee);
            }
        }
        return $attendees;
    }

    /**
     * Vrací počet programů bloku.
     * @return int
     */
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
     * @return Subevent
     */
    public function getSubevent()
    {
        return $this->subevent;
    }

    /**
     * @param Subevent $subevent
     */
    public function setSubevent($subevent)
    {
        $this->subevent = $subevent;
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
     * Je uživatel oprávněn přihlašovat se na programy bloku?
     * @param User $user
     * @return bool
     */
    public function isAllowed(User $user)
    {
        $result = TRUE;

        if ($this->category) {
            $tmp = FALSE;
            foreach ($user->getRoles() as $role) {
                if ($role->getRegisterableCategories()->contains($this->category)) {
                    $tmp = TRUE;
                    break;
                }
            }
            if (!$tmp)
                $result = FALSE;
        }

        $tmp = FALSE;
        foreach ($user->getNotCanceledSubeventsApplications() as $application) {
            if ($application->getSubevents()->contains($this->subevent)) {
                $tmp = TRUE;
                break;
            }
        }
        if (!$tmp)
            $result = FALSE;

        return $result;
    }

    /**
     * Účastní se uživatel programu bloku?
     * @param User $user
     * @return bool
     */
    public function isAttendee(User $user)
    {
        foreach ($this->programs as $program) {
            if ($program->isAttendee($user))
                return TRUE;
        }
        return FALSE;
    }
}
