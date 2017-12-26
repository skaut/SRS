<?php

namespace App\Model\User;

use App\Model\Structure\Subevent;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita přihláška podakcí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="SubeventsApplicationRepository")
 */
class SubeventsApplication extends Application
{
    protected $type = Application::SUBEVENTS;

    /**
     * Podakce.
     * @ORM\ManyToMany(targetEntity="\App\Model\Structure\Subevent", inversedBy="applications", cascade={"persist"})
     * @var Collection
     */
    protected $subevents;


    /**
     * SubeventsApplication constructor.
     */
    public function __construct()
    {
        $this->subevents = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getSubevents(): Collection
    {
        return $this->subevents;
    }

    /**
     * @param Collection $subevents
     */
    public function setSubevents(Collection $subevents): void
    {
        $this->subevents = $subevents;
    }

    /**
     * Vrací názvy podakcí oddělené čárkou.
     * @return string
     */
    public function getSubeventsText() : string
    {
        return implode(', ', $this->subevents->map(function (Subevent $subevent) {return $subevent->getName();})->toArray());
    }

    public function getRolesText(): ?string
    {
        return NULL;
    }
}
