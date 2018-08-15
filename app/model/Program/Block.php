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


    public function __construct()
    {
        $this->programs = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    /**
     * @return Collection
     */
    public function getPrograms() : Collection
    {
        return $this->programs;
    }

    /**
     * Vrací seznam účastníků bloku.
     * @return Collection
     */
    public function getAttendees() : Collection
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
     */
    public function getProgramsCount() : int
    {
        return $this->programs->count();
    }

    public function getLector() : ?User
    {
        return $this->lector;
    }

    public function setLector(?User $lector) : void
    {
        $this->lector = $lector;
    }

    public function getCategory() : ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category) : void
    {
        $this->category = $category;
    }

    public function getSubevent() : Subevent
    {
        return $this->subevent;
    }

    public function setSubevent(Subevent $subevent) : void
    {
        $this->subevent = $subevent;
    }

    public function getMandatory() : int
    {
        return $this->mandatory;
    }

    public function setMandatory(int $mandatory) : void
    {
        $this->mandatory = $mandatory;
    }

    public function getDuration() : int
    {
        return $this->duration;
    }

    public function setDuration(int $duration) : void
    {
        $this->duration = $duration;
    }

    public function getCapacity() : ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity) : void
    {
        $this->capacity = $capacity;
    }

    public function getTools() : ?string
    {
        return $this->tools;
    }

    public function setTools(?string $tools) : void
    {
        $this->tools = $tools;
    }

    public function getPerex() : ?string
    {
        return $this->perex;
    }

    public function setPerex(?string $perex) : void
    {
        $this->perex = $perex;
    }

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : void
    {
        $this->description = $description;
    }

    /**
     * Je uživatel oprávněn přihlašovat se na programy bloku?
     */
    public function isAllowed(User $user) : bool
    {
        $result = true;

        if ($this->category) {
            $tmp = false;
            foreach ($user->getRoles() as $role) {
                if ($role->getRegisterableCategories()->contains($this->category)) {
                    $tmp = true;
                    break;
                }
            }
            if (! $tmp) {
                $result = false;
            }
        }

        $tmp = false;
        foreach ($user->getNotCanceledSubeventsApplications() as $application) {
            if ($application->getSubevents()->contains($this->subevent)) {
                $tmp = true;
                break;
            }
        }
        if (! $tmp) {
            $result = false;
        }

        return $result;
    }

    /**
     * Účastní se uživatel programu bloku?
     */
    public function isAttendee(User $user) : bool
    {
        foreach ($this->programs as $program) {
            if ($program->isAttendee($user)) {
                return true;
            }
        }
        return false;
    }
}
