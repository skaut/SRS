<?php

declare(strict_types=1);

namespace App\Model\Program;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita místnost.
 *
 * @ORM\Entity
 * @ORM\Table(name="room")
 */
class Room
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=FALSE)
     */
    private ?int $id;

    /**
     * Název místnosti.
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected string $name;

    /**
     * Kapacita.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $capacity = null;

    /**
     * Programy v místnosti.
     *
     * @ORM\OneToMany(targetEntity="Program", mappedBy="room", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     *
     * @var Collection<int, Program>
     */
    protected Collection $programs;

    public function __construct(string $name, ?int $capacity)
    {
        $this->name     = $name;
        $this->capacity = $capacity;

        $this->programs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): void
    {
        $this->capacity = $capacity;
    }

    /**
     * @return Collection<int, Program>
     */
    public function getPrograms(): Collection
    {
        return $this->programs;
    }

    public function addProgram(Program $program): void
    {
        if (! $this->programs->contains($program)) {
            $this->programs->add($program);
            $program->setRoom($this);
        }
    }

    public function removeProgram(Program $program): void
    {
        if ($this->programs->contains($program)) {
            $this->programs->removeElement($program);
            $program->setRoom(null);
        }
    }
}
