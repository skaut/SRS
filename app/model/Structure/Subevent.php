<?php

namespace App\Model\Structure;

use App\Model\ACL\Role;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita podakce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
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
    protected $implicit = FALSE;

    /**
     * Přihlášky.
     * @ORM\ManyToMany(targetEntity="\App\Model\User\Application", mappedBy="subevents", cascade={"persist"})
     * @var Collection
     */
    protected $applications;

    /**
     * Bloky v podakci.
     * @ORM\OneToMany(targetEntity="\App\Model\Program\Block", mappedBy="subevent", cascade={"persist"})
     * @ORM\OrderBy({"name" = "ASC"})
     * @var Collection
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
     * Podakce neregistrovatelné současně s touto podakcí.
     * @ORM\ManyToMany(targetEntity="Subevent")
     * @ORM\JoinTable(name="subevent_subevent_incompatible",
     *      joinColumns={@ORM\JoinColumn(name="subevent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="incompatible_subevent_id", referencedColumnName="id")}
     *      )
     * @var Collection
     */
    protected $incompatibleSubevents;

    /**
     * Podakce vyžadující tuto podakci.
     * @ORM\ManyToMany(targetEntity="Subevent", mappedBy="requiredSubevents", cascade={"persist"})
     * @var Collection
     */
    protected $requiredBySubevent;

    /**
     * Podakce vyžadované touto podakcí.
     * @ORM\ManyToMany(targetEntity="Subevent", inversedBy="requiredBySubevent", cascade={"persist"})
     * @ORM\JoinTable(name="subevent_subevent_required",
     *      joinColumns={@ORM\JoinColumn(name="subevent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="required_subevent_id", referencedColumnName="id")}
     *      )
     * @var Collection
     */
    protected $requiredSubevents;


    /**
     * Subevent constructor.
     */
    public function __construct()
    {
        $this->applications = new ArrayCollection();
        $this->blocks = new ArrayCollection();
        $this->incompatibleSubevents = new ArrayCollection();
        $this->requiredBySubevent = new ArrayCollection();
        $this->requiredSubevents = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isImplicit() : bool
    {
        return $this->implicit;
    }

    /**
     * @param bool $implicit
     */
    public function setImplicit(bool $implicit) : void
    {
        $this->implicit = $implicit;
    }

    /**
     * @return Collection
     */
    public function getBlocks() : Collection
    {
        return $this->blocks;
    }

    /**
     * @param Collection $blocks
     */
    public function setBlocks(Collection $blocks) : void
    {
        $this->blocks = $blocks;
    }

    /**
     * @return int
     */
    public function getFee() : int
    {
        return $this->fee;
    }

    /**
     * @param int $fee
     */
    public function setFee(int $fee) : void
    {
        $this->fee = $fee;
    }

    /**
     * @return int
     */
    public function getCapacity() : ?int
    {
        return $this->capacity;
    }

    /**
     * @param int $capacity
     */
    public function setCapacity(?int $capacity) : void
    {
        $this->capacity = $capacity;
    }

    /**
     * @return bool
     */
    public function hasLimitedCapacity() : bool
    {
        return $this->capacity !== NULL;
    }

    /**
     * @return Collection|Subevent[]
     */
    public function getIncompatibleSubevents() : Collection
    {
        return $this->incompatibleSubevents;
    }

    /**
     * @param $incompatibleSubevents
     */
    public function setIncompatibleSubevents(Collection $incompatibleSubevents) : void
    {
        foreach ($this->getIncompatibleSubevents() as $subevent) {
            if (!$incompatibleSubevents->contains($subevent))
                $subevent->getIncompatibleSubevents()->removeElement($this);
        }
        foreach ($incompatibleSubevents as $subevent) {
            if (!$subevent->getIncompatibleSubevents()->contains($this))
                $subevent->getIncompatibleSubevents()->add($this);
        }

        $this->incompatibleSubevents = $incompatibleSubevents;
    }

    /**
     * @param $subevent
     */
    public function addIncompatibleSubevent(Subevent $subevent) : void
    {
        if (!$this->incompatibleSubevents->contains($subevent))
            $this->incompatibleSubevents->add($subevent);
    }

    /**
     * Vrací názvy všech nekompatibilních podakcí.
     * @return string
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
     * @param $allRequiredBySubevent
     * @param $subevent
     */
    private function getRequiredBySubeventTransitiveRec(Collection &$allRequiredBySubevent, Subevent $subevent) : void
    {
        if ($this === $subevent || $allRequiredBySubevent->contains($subevent))
            return;

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
     * @param $requiredSubevents
     */
    public function setRequiredSubevents(Collection $requiredSubevents) : void
    {
        $this->requiredSubevents = $requiredSubevents;
    }

    /**
     * @param $subevent
     */
    public function addRequiredSubevent(Subevent $subevent) : void
    {
        if (!$this->requiredSubevents->contains($subevent))
            $this->requiredSubevents->add($subevent);
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
     * @param $allRequiredSubevents
     * @param $subevent
     */
    private function getRequiredSubeventsTransitiveRec(Collection &$allRequiredSubevents, Subevent $subevent) : void
    {
        if ($this === $subevent || $allRequiredSubevents->contains($subevent))
            return;

        $allRequiredSubevents->add($subevent);

        foreach ($subevent->requiredSubevents as $requiredSubevent) {
            $this->getRequiredSubeventsTransitiveRec($allRequiredSubevents, $requiredSubevent);
        }
    }

    /**
     * Vrací názvy všech vyžadovaných podakcí.
     * @return string
     */
    public function getRequiredSubeventsTransitiveText() : string
    {
        $requiredSubeventsNames = [];
        foreach ($this->getRequiredSubeventsTransitive() as $requiredSubevent) {
            $requiredSubeventsNames[] = $requiredSubevent->getName();
        }
        return implode(', ', $requiredSubeventsNames);
    }
}
