<?php

declare(strict_types=1);

namespace App\Model\Program;

use App\Model\Structure\Subevent;
use App\Model\User\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;
use function implode;

/**
 * Entita programový blok.
 *
 * @ORM\Entity(repositoryClass="\App\Model\Program\Repositories\BlockRepository")
 * @ORM\Table(name="block")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Block
{
    use Id;

    /**
     * Název programového bloku.
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected string $name;

    /**
     * Programy v bloku.
     *
     * @ORM\OneToMany(targetEntity="Program", mappedBy="block", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     *
     * @var Collection|Program[]
     */
    protected Collection $programs;

    /**
     * Lektor.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\User\User", inversedBy="lecturersBlocks", cascade={"persist"})
     *
     * @var Collection|User[]
     */
    protected Collection $lectors;

    /**
     * Kategorie bloku.
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="blocks", cascade={"persist"})
     */
    protected ?Category $category = null;

    /**
     * Podakce bloku.
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\Structure\Subevent", inversedBy="blocks", cascade={"persist"})
     */
    protected Subevent $subevent;

    /**
     * Povinnost.
     *
     * @ORM\Column(type="string")
     */
    protected string $mandatory;

    /**
     * Délka programového bloku.
     *
     * @ORM\Column(type="integer")
     */
    protected int $duration;

    /**
     * Kapacita.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $capacity = null;

    /**
     * Povoleno přihlašování náhradníků?
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $alternatesAllowed = true;

    /**
     * Pomůcky.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $tools = null;

    /**
     * Stručný popis.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $perex = null;

    /**
     * Podrobný popis.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $description = null;

    public function __construct()
    {
        $this->programs = new ArrayCollection();
        $this->lectors  = new ArrayCollection();
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
     * @return Collection|Program[]
     */
    public function getPrograms() : Collection
    {
        return $this->programs;
    }

    /**
     * Vrací počet programů bloku.
     */
    public function getProgramsCount() : int
    {
        return $this->programs->count();
    }

    /**
     * @return Collection|User[]
     */
    public function getLectors() : Collection
    {
        return $this->lectors;
    }

    public function getLectorsText() : string
    {
        return implode(', ', $this->lectors->map(static function (User $lector) {
            return $lector->getDisplayName();
        })->toArray());
    }

    /**
     * @param Collection|User[] $lectors
     */
    public function setLectors(Collection $lectors) : void
    {
        $this->lectors->clear();
        foreach ($lectors as $lector) {
            $this->lectors->add($lector);
        }
    }

    public function getCategory() : ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category) : void
    {
        $this->category = $category;
    }

    public function getSubevent() : ?Subevent
    {
        return $this->subevent;
    }

    public function setSubevent(Subevent $subevent) : void
    {
        $this->subevent = $subevent;
    }

    public function getMandatory() : string
    {
        return $this->mandatory;
    }

    public function setMandatory(string $mandatory) : void
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

    public function isAlternatesAllowed(): bool
    {
        return true; // todo
        return $this->alternatesAllowed;
    }

    public function setAlternatesAllowed(bool $alternatesAllowed): void
    {
        $this->alternatesAllowed = $alternatesAllowed;
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
}
