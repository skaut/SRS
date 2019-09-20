<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Skautis\Wsdl\WsdlException;
use Tracy\Debugger;
use Tracy\ILogger;
use function array_key_exists;

/**
 * Služba pro správu obecné skautIS akce.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsEventGeneralService extends SkautIsEventService
{
    public function isEventDraft(int $eventId) : bool
    {
        return $this->getEventDetail($eventId)->ID_EventGeneralState === 'draft';
    }

    /**
     * Vloží účastníky do skautIS.
     * @param Collection|User[] $users
     */
    public function insertParticipants(int $eventId, Collection $users, bool $accept = false) : bool
    {
        try {
            $participants = [];

            foreach ($this->getAllParticipants($eventId) as $participant) {
                $participants[$participant->ID_Person] = true;
            }

            foreach ($users as $user) {
                $personId = $user->getSkautISPersonId();

                if (array_key_exists($personId, $participants)) {
                    continue;
                }

                $this->insertParticipant($eventId, $personId);
            }
        } catch (WsdlException $ex) {
            Debugger::log($ex, ILogger::WARNING);
            return false;
        }

        return true;
    }

    protected function getEventDetail(int $eventId) : \stdClass
    {
        return $this->skautIs->event->EventGeneralDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $eventId,
        ]);
    }

    /**
     * @return \stdClass[]
     */
    protected function getDraftEvents() : array
    {
        $events = $this->skautIs->event->EventGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneralState' => 'draft',
        ]);

        if ($events instanceof \stdClass) {
            return [];
        }

        return $events;
    }

    /**
     * Vrací účastníky akce.
     * @return \stdClass[]
     */
    private function getAllParticipants(int $eventId) : array
    {
        $participants = $this->skautIs->event->ParticipantGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId,
        ]);

        if ($participants instanceof \stdClass) {
            return [];
        }

        return $participants;
    }

    /**
     * Přidá účastníka akce.
     */
    private function insertParticipant(int $eventId, int $personId) : void
    {
        $this->skautIs->event->ParticipantGeneralInsert([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId,
            'ID_Person' => $personId,
        ]);
    }
}
