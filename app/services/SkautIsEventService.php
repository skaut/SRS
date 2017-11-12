<?php

namespace App\Services;
use App\Model\User\User;
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
     * Synchronizuje účastníky.
     * @param $eventId
     * @param User[] $participants
     */
    public function syncEventParticipants($eventId, array $participants)
    {
        $skautIsParticipants = $this->getAllParticipants($eventId);

        foreach ($skautIsParticipants as $p) {
            if ($p->CanDelete)
                $this->deleteParticipant($p->ID);
        }

        foreach ($participants as $p) {
            if (!$p->isExternal())
                $this->insertParticipant($p->getSkautISPersonId(), $eventId);
        }
    }

    /**
     * Vrací název akce.
     * @param $eventId
     * @return string
     */
    public function getEventDisplayName($eventId) {
        return $this->getEventDetail($eventId)->DisplayName;
    }

    /**
     * Vrací true, pokud je akce neuzavřená.
     * @param $eventId
     * @return bool
     */
    public abstract function isEventDraft($eventId);

    /**
     * Vrací seznam neuzavřených akcí pro select.
     * @return array
     */
    public function getEventsOptions() {
        $options = [];
        try {
            foreach ($this->getDraftEvents() as $e)
                $options[$e->ID] = $e->DisplayName;
        } catch (WsdlException $ex) {
        }
        return $options;
    }

    /**
     * Vrací údaje o akci.
     * @param $eventId
     * @return mixed
     */
    protected abstract function getEventDetail($eventId);

    /**
     * Vrátí všehny účastníky skautIS akce.
     * @param $eventId
     * @return array
     */
    protected abstract function getAllParticipants($eventId);

    /**
     * Přidává účastníka do skautIS.
     * @param $participantId
     * @param $eventId
     */
    protected abstract function insertParticipant($participantId, $eventId);

    /**
     * Odstraňuje účastníka ze skautIS.
     * @param $participantId
     */
    protected abstract function deleteParticipant($participantId);

    /**
     * Vrací seznam neuzavřených akcí.
     * @return mixed
     */
    protected abstract function getDraftEvents();
}