<?php

namespace App\Model\User;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


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
