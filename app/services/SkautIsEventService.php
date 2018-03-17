<?php

namespace App\Services;

use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;


/**
 * Služba pro správu skautIS akce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
abstract class SkautIsEventService
{
    /** @var Skautis */
    protected $skautIs;


    /**
     * SkautIsEventService constructor.
     * @param Skautis $skautIs
     */
    public function __construct(Skautis $skautIs)
    {
        $this->skautIs = $skautIs;
    }

    /**
     * Vrací true, pokud je akce neuzavřená.
     * @param $eventId
     * @return bool
     */
    abstract public function isEventDraft($eventId);

    /**
     * Vloží účastníky do skautIS.
     * @param int $eventId
     * @param Collection|User[] $users
     * @param bool $accept Přijetí účastníků (pouze u vzdělávacích akcí).
     * @return bool
     */
    abstract public function insertParticipants(int $eventId, Collection $users, bool $accept = FALSE): bool;

    /**
     * Vrací údaje o akci.
     * @param $eventId
     * @return mixed
     */
    abstract protected function getEventDetail($eventId);

    /**
     * Vrací seznam neuzavřených akcí.
     * @return mixed
     */
    abstract protected function getDraftEvents();

    /**
     * Vrací název akce.
     * @param $eventId
     * @return string
     */
    public function getEventDisplayName($eventId)
    {
        return $this->getEventDetail($eventId)->DisplayName;
    }

    /**
     * Vrací seznam neuzavřených akcí pro select.
     * @return array
     */
    public function getEventsOptions()
    {
        $options = [];
        try {
            foreach ($this->getDraftEvents() as $event)
                $options[$event->ID] = $event->DisplayName;
        } catch (WsdlException $e) {
        }
        return $options;
    }
}
