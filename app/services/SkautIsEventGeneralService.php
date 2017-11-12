<?php

namespace App\Services;


/**
 * Služba pro správu obecné skautIS akce.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsEventGeneralService extends SkautIsEventService
{
    public function isEventDraft($eventId)
    {
        return $this->getEventDetail($eventId)->ID_EventGeneralState == 'draft';
    }

    protected function getEventDetail($eventId)
    {
        return $this->skautIs->event->EventGeneralDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $eventId
        ]);
    }

    protected function getAllParticipants($eventId)
    {
        return $this->skautIs->event->ParticipantGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId
        ]);
    }

    protected function insertParticipant($participantId, $eventId)
    {
        $this->skautIs->event->ParticipantGeneralInsert([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId,
            'ID_Person' => $participantId
        ]);
    }

    protected function deleteParticipant($participantId)
    {
        $this->skautIs->event->ParticipantGeneralDelete([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $participantId,
            'DeletePerson' => FALSE
        ]);
    }

    protected function getDraftEvents()
    {
        return $this->skautIs->event->EventGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneralState' => 'draft'
        ]);
    }
}