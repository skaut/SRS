<?php

declare(strict_types=1);

namespace App\Model\Application;

use App\Model\Structure\Subevent;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita přihláška podakcí.
 */
#[ORM\Entity]
class SubeventsApplication extends Application
{
    protected string $type = Application::SUBEVENTS;

    /** @param Collection<int, Subevent> $subevents */
    public function setSubevents(Collection $subevents): void
    {
        foreach ($this->subevents as $subevent) {
            $this->removeSubevent($subevent);
        }

        foreach ($subevents as $subevent) {
            $this->addSubevent($subevent);
        }
    }

    public function addSubevent(Subevent $subevent): void
    {
        if (! $this->subevents->contains($subevent)) {
            $this->subevents->add($subevent);
            $subevent->addApplication($this);
        }
    }

    public function removeSubevent(Subevent $subevent): void
    {
        if ($this->subevents->contains($subevent)) {
            $this->subevents->removeElement($subevent);
            $subevent->removeApplication($this);
        }
    }
}
