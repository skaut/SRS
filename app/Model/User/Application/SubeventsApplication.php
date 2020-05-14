<?php

declare(strict_types=1);

namespace App\Model\User\Application;

use App\Model\Structure\Subevent;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita přihláška podakcí.
 *
 * @ORM\Entity(repositoryClass="SubeventsApplicationRepository")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SubeventsApplication extends Application
{
    protected string $type = Application::SUBEVENTS;

    /**
     * @param Collection|Subevent[] $subevents
     */
    public function setSubevents(Collection $subevents) : void
    {
        $this->subevents->clear();
        foreach ($subevents as $subevent) {
            $this->subevents->add($subevent);
        }
    }
}
