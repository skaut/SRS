<?php

declare(strict_types=1);

namespace App\Model\Program;

use DateInterval;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Exception;

/**
 * Entita program.
 */
#[ORM\Entity]
#[ORM\Table(name: 'program')]
class Program
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', nullable: false)]
    private int|null $id = null;

    /**
     * Programový blok.
     */
    #[ORM\ManyToOne(targetEntity: Block::class, inversedBy: 'programs', cascade: ['persist'])]
    protected Block $block;

    /**
     * Přihlášky na program.
     *
     * @var Collection<int, ProgramApplication>
     */
    #[ORM\OneToMany(targetEntity: ProgramApplication::class, mappedBy: 'program', cascade: ['persist'])]
    protected Collection $programApplications;

    /**
     * Počet účastníků.
     */
    #[ORM\Column(type: 'integer')]
    protected int $attendeesCount = 0;

    /**
     * Místnost.
     */
    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'programs', cascade: ['persist'])]
    protected Room|null $room = null;

    /**
     * Začátek programu.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    protected DateTimeImmutable $start;

    public function __construct(DateTimeImmutable $start)
    {
        $this->start               = $start;
        $this->programApplications = new ArrayCollection();
    }

    public function getId(): int|null
    {
        return $this->id;
    }

    public function getBlock(): Block
    {
        return $this->block;
    }

    public function setBlock(Block $block): void
    {
        $block->addProgram($this);
        $this->block = $block;
    }

    public function getAttendeesCount(): int
    {
        return $this->attendeesCount;
    }

    public function setAttendeesCount(int $attendeesCount): void
    {
        $this->attendeesCount = $attendeesCount;
    }

    public function getAlternatesCount(): int
    {
        return $this->programApplications->matching(
            Criteria::create()->where(
                Criteria::expr()->eq('alternate', true),
            ),
        )->count();
    }

    public function getRoom(): Room|null
    {
        return $this->room;
    }

    public function setRoom(Room|null $room): void
    {
        if ($this->room !== null) {
            $this->room->removeProgram($this);
        }

        if ($room !== null) {
            $room->addProgram($this);
        }

        $this->room = $room;
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    public function setStart(DateTimeImmutable $start): void
    {
        $this->start = $start;
    }

    /**
     * Vrací kapacitu programového bloku.
     */
    public function getBlockCapacity(): int|null
    {
        return $this->block->getCapacity();
    }

    /**
     * Vrací konec programu vypočtený podle délky bloku.
     *
     * @throws Exception
     */
    public function getEnd(): DateTimeImmutable
    {
        return $this->start->add(new DateInterval('PT' . $this->block->getDuration() . 'M'));
    }
}
