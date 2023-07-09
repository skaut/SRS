<?php

declare(strict_types=1);

namespace App\Model\Program;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita místnost.
 */
#[ORM\Entity]
#[ORM\Table(name: 'room')]
class Room
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Název místnosti.
     */
    #[ORM\Column(type: 'string', unique: true)]
    protected string $name;

    /**
     * Kapacita.
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    protected int|null $capacity;

    /**
     * Programy v místnosti.
     *
     * @var Collection<int, Program>
     */
    #[ORM\OneToMany(targetEntity: Program::class, mappedBy: 'room', cascade: ['persist'])]
    #[ORM\OrderBy(['start' => 'ASC'])]
    protected Collection $programs;

    public function __construct(string $name, int|null $capacity)
    {
        $this->name     = $name;
        $this->capacity = $capacity;
        $this->programs = new ArrayCollection();
    }

    public function getId(): int|null
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

    public function getCapacity(): int|null
    {
        return $this->capacity;
    }

    public function setCapacity(int|null $capacity): void
    {
        $this->capacity = $capacity;
    }

    /** @return Collection<int, Program> */
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
