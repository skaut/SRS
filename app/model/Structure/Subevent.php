<?php

namespace App\Model\Structure;

use Doctrine\Common\Collections\ArrayCollection;
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
     * Bloky v podakci.
     * @ORM\OneToMany(targetEntity="\App\Model\Program\Block", mappedBy="subevent", cascade={"persist"})
     * @ORM\OrderBy({"name" = "ASC"})
     * @var ArrayCollection
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
     * @var ArrayCollection
     */
    protected $incompatibleSubevents;

    /**
     * Podakce vyžadující tuto podakci.
     * @ORM\ManyToMany(targetEntity="Subevent", mappedBy="requiredSubevents", cascade={"persist"})
     * @var ArrayCollection
     */
    protected $requiredBySubevent;

    /**
     * Podakce vyžadované touto podakcí.
     * @ORM\ManyToMany(targetEntity="Subevent", inversedBy="requiredBySubevent", cascade={"persist"})
     * @ORM\JoinTable(name="subevent_subevent_required",
     *      joinColumns={@ORM\JoinColumn(name="subevent_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="required_subevent_id", referencedColumnName="id")}
     *      )
     * @var ArrayCollection
     */
    protected $requiredSubevents;


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
     * @return bool
     */
    public function isImplicit()
    {
        return $this->implicit;
    }

    /**
     * @param bool $implicit
     */
    public function setImplicit($implicit)
    {
        $this->implicit = $implicit;
    }

    /**
     * @return ArrayCollection
     */
    public function getBlocks()
    {
        return $this->blocks;
    }

    /**
     * @param ArrayCollection $blocks
     */
    public function setBlocks($blocks)
    {
        $this->blocks = $blocks;
    }

    /**
     * @return int
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param int $fee
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
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
     * @return ArrayCollection
     */
    public function getIncompatibleSubevents()
    {
        return $this->incompatibleSubevents;
    }

    /**
     * @param $incompatibleSubevents
     */
    public function setIncompatibleSubevents($incompatibleSubevents)
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
    public function addIncompatibleSubevent($subevent)
    {
        if (!$this->incompatibleSubevents->contains($subevent))
            $this->incompatibleSubevents->add($subevent);
    }

    /**
     * @return ArrayCollection
     */
    public function getRequiredBySubevent()
    {
        return $this->requiredBySubevent;
    }

    /**
     * Vrací všechny (tranzitivně) podakce, kterými je tato podakce vyžadována.
     * @return array
     */
    public function getRequiredBySubeventTransitive()
    {
        $allRequiredBySubevent = [];
        foreach ($this->requiredBySubevent as $requiredBySubevent) {
            $this->getRequiredBySubeventTransitiveRec($allRequiredBySubevent, $requiredBySubevent);
        }
        return $allRequiredBySubevent;
    }

    private function getRequiredBySubeventTransitiveRec(&$allRequiredBySubevent, $subevent)
    {
        if ($this == $subevent || in_array($subevent, $allRequiredBySubevent))
            return;

        $allRequiredBySubevent[] = $subevent;

        foreach ($subevent->requiredBySubevent as $requiredBySubevent) {
            $this->getRequiredBySubeventTransitiveRec($allRequiredBySubevent, $requiredBySubevent);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getRequiredSubevents()
    {
        return $this->requiredSubevents;
    }

    /**
     * @param $requiredSubevents
     */
    public function setRequiredSubevents($requiredSubevents)
    {
        $this->requiredSubevents = $requiredSubevents;
    }

    /**
     * @param $subevent
     */
    public function addRequiredSubevent($subevent)
    {
        if (!$this->requiredSubevents->contains($subevent))
            $this->requiredSubevents->add($subevent);
    }

    /**
     * Vrací všechny (tranzitivně) vyžadované podakce.
     * @return array
     */
    public function getRequiredSubeventsTransitive()
    {
        $allRequiredSubevents = [];
        foreach ($this->requiredSubevents as $requiredSubevent) {
            $this->getRequiredSubeventsTransitiveRec($allRequiredSubevents, $requiredSubevent);
        }
        return $allRequiredSubevents;
    }

    private function getRequiredSubeventsTransitiveRec(&$allRequiredSubevents, $subevent)
    {
        if ($this == $subevent || in_array($subevent, $allRequiredSubevents))
            return;

        $allRequiredSubevents[] = $subevent;

        foreach ($subevent->requiredSubevents as $requiredSubevent) {
            $this->getRequiredSubeventsTransitiveRec($allRequiredSubevents, $requiredSubevent);
        }
    }
}
