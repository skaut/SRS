<?php

declare(strict_types=1);

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


    public function __construct(Skautis $skautIs)
    {
        $this->skautIs = $skautIs;
    }

    /**
     * Vrací true, pokud je akce neuzavřená.
     * @param $eventId
     */
    abstract public function isEventDraft($eventId) : bool;

    /**
     * Vloží účastníky do skautIS.
     * @param Collection|User[] $users
     * @param bool              $accept Přijetí účastníků (pouze u vzdělávacích akcí).
     */
    abstract public function insertParticipants(int $eventId, Collection $users, bool $accept = false) : bool;

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
     */
    public function getEventDisplayName($eventId) : string
    {
        return $this->getEventDetail($eventId)->DisplayName;
    }

    /**
     * Vrací seznam neuzavřených akcí pro select.
     * @return array
     */
    public function getEventsOptions() : array
    {
        $options = [];
        try {
            foreach ($this->getDraftEvents() as $event) {
                $options[$event->ID] = $event->DisplayName;
            }
        } catch (WsdlException $e) {
        }
        return $options;
    }
}
