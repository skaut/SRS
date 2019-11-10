<?php

declare(strict_types=1);

namespace App\Model\Program;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id as Identifier;

/**
 * Entita místnost.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @ORM\Entity(repositoryClass="RoomRepository")
 * @ORM\Table(name="room")
 */
class Room
{
    use Identifier;

    /**
     * Název místnosti.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * Kapacita.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $capacity;

    /**
     * Programy v místnosti.
     * @ORM\OneToMany(targetEntity="Program", mappedBy="room", cascade={"persist"})
     * @ORM\OrderBy({"start" = "ASC"})
     * @var Collection|Program[]
     */
    protected $programs;


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

    public function getCapacity() : ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity) : void
    {
        $this->capacity = $capacity;
    }

    /**
     * @return Collection|Program[]
     */
    public function getPrograms() : Collection
    {
        return $this->programs;
    }
}
