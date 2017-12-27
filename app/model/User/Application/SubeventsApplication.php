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
     * @param Collection $subevents
     */
    public function setSubevents(Collection $subevents): void
    {
        $this->subevents = $subevents;
    }
}
