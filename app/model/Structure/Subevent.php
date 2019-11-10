<?php

declare(strict_types=1);

namespace App\Model\Structure;

use App\Model\Enums\ApplicationState;
use App\Model\Program\Block;
use App\Model\SkautIs\SkautIsCourse;
use App\Model\User\Application;
use App\Model\User\SubeventsApplication;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id as Identifier;
use function implode;

/**
 * Entita podakce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @ORM\Entity(repositoryClass="SubeventRepository")
 * @ORM\Table(name="subevent")
 */
class Subevent
{
    use Identifier;

    /**
     * Název podakce.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;

    /**
     * Implicitní podakce. Vytvořena automaticky.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $implicit = false;

    /**
     * Přihlášky.
     * @ORM\ManyToMany(targetEntity="\App\Model\User\SubeventsApplication", mappedBy="subevents", cascade={"persist"})
     * @var Collection|SubeventsApplication[]
     */
    protected $applications;

    /**
     * Bloky v podakci.
     * @ORM\OneToMany(targetEntity="\App\Model\Program\Block", mappedBy="subevent", cascade={"persist"})
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection|Block[]
     */
    protected $blocks;

    /**
     * Poplatek.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $fee = 0;

    /**
     * Kapacita.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $capacity;

    /**
     * Obsazenost.
     * Bude se používat pro kontrolu kapacity.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $occupancy = 0;

    /**
     * Podakce neregistrovatelné současně s touto podakcí.
     * @ORM\ManyToMany(targetEntity="Subevent")
     * @ORM\JoinTable(name="subevent_subevent_incompatible",
     *      joinColumns={@ORM\JoinColumn(name="subevent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_subevent_id", referencedColumnName="id")}
     *      )
     * @var Collection|Subevent[]
     */
    protected $incompatibleSubevents;

    /**
     * Podakce vyžadující tuto podakci.
     * @ORM\ManyToMany(targetEntity="Subevent", mappedBy="requiredSubevents", cascade={"persist"})
     * @var Collection|Subevent[]
     */
    protected $requiredBySubevent;

    /**
     * Podakce vyžadované touto podakcí.
     * @ORM\ManyToMany(targetEntity="Subevent", inversedBy="requiredBySubevent")
     * @ORM\JoinTable(name="subevent_subevent_required",
     *      joinColumns={@ORM\JoinColumn(name="subevent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="required_subevent_id", referencedColumnName="id")}
     *      )
     * @var Collection|Subevent[]
     */
    protected $requiredSubevents;

    /**
     * Propojené skautIS kurzy.
     * @ORM\ManyToMany(targetEntity="\App\Model\SkautIs\SkautIsCourse")
     * @var Collection|SkautIsCourse[]
     */
    protected $skautIsCourses;


    public function __construct()
    {
        $this->applications          = new ArrayCollection();
        $this->blocks                = new ArrayCollection();
        $this->incompatibleSubevents = new ArrayCollection();
        $this->requiredBySubevent    = new ArrayCollection();
        $this->requiredSubevents     = new ArrayCollection();
        $this->skautIsCourses        = new ArrayCollection();
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

    public function isImplicit() : bool
    {
        return $this->implicit;
    }

    public function setImplicit(bool $implicit) : void
    {
        $this->implicit = $implicit;
    }

    /**
     * @return Collection|Block[]
     */
    public function getBlocks() : Collection
    {
        return $this->blocks;
    }

    public function getFee() : int
    {
        return $this->fee;
    }

    public function setFee(int $fee) : void
    {
        $this->fee = $fee;
    }

    public function getCapacity() : ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity) : void
    {
        $this->capacity = $capacity;
    }

    public function hasLimitedCapacity() : bool
    {
        return $this->capacity !== null;
    }

    public function getOccupancy() : int
    {
        return $this->occupancy;
    }

    /**
     * @return Collection|Subevent[]
     */
    public function getIncompatibleSubevents() : Collection
    {
        return $this->incompatibleSubevents;
    }

    /**
     * @param Collection|Subevent[] $incompatibleSubevents
     */
    public function setIncompatibleSubevents(Collection $incompatibleSubevents) : void
    {
        foreach ($this->getIncompatibleSubevents() as $subevent) {
            if ($incompatibleSubevents->contains($subevent)) {
                continue;
            }

            $subevent->getIncompatibleSubevents()->removeElement($this);
        }
        foreach ($incompatibleSubevents as $subevent) {
            if ($subevent->getIncompatibleSubevents()->contains($this)) {
                continue;
            }

            $subevent->getIncompatibleSubevents()->add($this);
        }

        $this->incompatibleSubevents = $incompatibleSubevents;
    }

    public function addIncompatibleSubevent(Subevent $subevent) : void
    {
        if ($this->incompatibleSubevents->contains($subevent)) {
            return;
        }

        $this->incompatibleSubevents->add($subevent);
    }

    /**
     * Vrací názvy všech nekompatibilních podakcí.
     */
    public function getIncompatibleSubeventsText() : string
    {
        $incompatibleSubeventsNames = [];
        foreach ($this->getIncompatibleSubevents() as $incompatibleSubevent) {
            $incompatibleSubeventsNames[] = $incompatibleSubevent->getName();
        }
        return implode(', ', $incompatibleSubeventsNames);
    }

    /**
     * @return Collection|Subevent[]
     */
    public function getRequiredBySubevent() : Collection
    {
        return $this->requiredBySubevent;
    }

    /**
     * Vrací všechny (tranzitivně) podakce, kterými je tato podakce vyžadována.
     * @return Collection|Subevent[]
     */
    public function getRequiredBySubeventTransitive() : Collection
    {
        $allRequiredBySubevent = new ArrayCollection();
        foreach ($this->requiredBySubevent as $requiredBySubevent) {
            $this->getRequiredBySubeventTransitiveRec($allRequiredBySubevent, $requiredBySubevent);
        }
        return $allRequiredBySubevent;
    }

    /**
     * @param Collection|Subevent[] $allRequiredBySubevent
     */
    private function getRequiredBySubeventTransitiveRec(Collection &$allRequiredBySubevent, Subevent $subevent) : void
    {
        if ($this === $subevent || $allRequiredBySubevent->contains($subevent)) {
            return;
        }

        $allRequiredBySubevent->add($subevent);

        foreach ($subevent->requiredBySubevent as $requiredBySubevent) {
            $this->getRequiredBySubeventTransitiveRec($allRequiredBySubevent, $requiredBySubevent);
        }
    }

    /**
     * @return Collection|Subevent[]
     */
    public function getRequiredSubevents() : Collection
    {
        return $this->requiredSubevents;
    }

    /**
     * @param Collection|Subevent[] $requiredSubevents
     */
    public function setRequiredSubevents(Collection $requiredSubevents) : void
    {
        $this->requiredSubevents->clear();
        foreach ($requiredSubevents as $requiredSubevent) {
            $this->requiredSubevents->add($requiredSubevent);
        }
    }

    /**
     * Vrací všechny (tranzitivně) vyžadované podakce.
     * @return Collection|Subevent[]
     */
    public function getRequiredSubeventsTransitive() : Collection
    {
        $allRequiredSubevents = new ArrayCollection();
        foreach ($this->requiredSubevents as $requiredSubevent) {
            $this->getRequiredSubeventsTransitiveRec($allRequiredSubevents, $requiredSubevent);
        }
        return $allRequiredSubevents;
    }

    /**
     * @param Collection|Subevent[] $allRequiredSubevents
     */
    private function getRequiredSubeventsTransitiveRec(Collection &$allRequiredSubevents, Subevent $subevent) : void
    {
        if ($this === $subevent || $allRequiredSubevents->contains($subevent)) {
            return;
        }

        $allRequiredSubevents->add($subevent);

        foreach ($subevent->requiredSubevents as $requiredSubevent) {
            $this->getRequiredSubeventsTransitiveRec($allRequiredSubevents, $requiredSubevent);
        }
    }

    /**
     * Vrací názvy všech vyžadovaných podakcí.
     */
    public function getRequiredSubeventsTransitiveText() : string
    {
        $requiredSubeventsNames = [];
        foreach ($this->getRequiredSubeventsTransitive() as $requiredSubevent) {
            $requiredSubeventsNames[] = $requiredSubevent->getName();
        }
        return implode(', ', $requiredSubeventsNames);
    }

    /**
     * @return Collection|SkautIsCourse[]
     */
    public function getSkautIsCourses() : Collection
    {
        return $this->skautIsCourses;
    }

    public function getSkautIsCoursesText() : string
    {
        return implode(', ', $this->skautIsCourses->map(function (SkautIsCourse $skautIsCourse) {
            return $skautIsCourse->getName();
        })->toArray());
    }

    /**
     * @param Collection|SkautIsCourse[] $skautIsCourses
     */
    public function setSkautIsCourses(Collection $skautIsCourses) : void
    {
        $this->skautIsCourses->clear();
        foreach ($skautIsCourses as $skautIsCourse) {
            $this->skautIsCourses->add($skautIsCourse);
        }
    }

    public function countUsers() : int
    {
        //TODO: opravit
//        $criteria = Criteria::create()
//            ->where(Criteria::expr()->andX(
//                Criteria::expr()->isNull('validTo'),
//                Criteria::expr()->orX(
//                    Criteria::expr()->eq('state', ApplicationState::WAITING_FOR_PAYMENT),
//                    Criteria::expr()->eq('state', ApplicationState::PAID),
//                    Criteria::expr()->eq('state', ApplicationState::PAID_FREE)
//                )
//            ));
//
//        return $this->applications->matching($criteria)->count();

        return $this->applications->filter(function (Application $application) {
            if ($application->getValidTo() === null && (
                $application->getState() === ApplicationState::WAITING_FOR_PAYMENT ||
                $application->getState() === ApplicationState::PAID_FREE ||
                $application->getState() === ApplicationState::PAID)) {
                return true;
            }
            return false;
        })->count();
    }

    public function countUnoccupied() : ?int
    {
        return $this->capacity ? $this->capacity - $this->countUsers() : null;
    }

    public function getOccupancyText() : string
    {
        return $this->capacity ? $this->countUsers() . '/' . $this->capacity : '' . $this->countUsers();
    }
}
