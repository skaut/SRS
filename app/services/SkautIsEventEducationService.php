<?php

namespace App\Services;


/**
 * Služba pro správu vzdělávací skautIS akce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsEventEducationService extends SkautIsEventService
{
    public function isEventDraft($eventId)
    {
        return $this->getEventDetail($eventId)->ID_EventEducationState == 'draft';
    }

    protected function getEventDetail($eventId)
    {
        return $this->skautIs->event->EventEducationDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $eventId
        ]);
    }

    protected function getAllParticipants($eventId)
    {
        return $this->skautIs->event->ParticipantEducationAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducation' => $eventId
        ]);

        //TODO https://test-is.skaut.cz/JunakWebservice/Events.asmx?op=ParticipantEducationAll
    }

    protected function insertParticipant($participantId, $eventId)
    {
        $this->skautIs->event->ParticipantEducationInsert([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducation' => $eventId,
            'ID_Person' => $participantId
        ]);

        //TODO https://test-is.skaut.cz/JunakWebservice/Events.asmx?op=ParticipantEducationInsert
    }

    protected function deleteParticipant($participantId)
    {
        $this->skautIs->event->ParticipantEducationDelete([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $participantId
        ]);

        //TODO https://test-is.skaut.cz/JunakWebservice/Events.asmx?op=ParticipantEducationDelete
    }

    protected function getDraftEvents()
    {
        return $this->skautIs->event->EventGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducationState' => 'draft'
        ]);

        //TODO https://test-is.skaut.cz/JunakWebservice/Events.asmx?op=EventEducationAll
    }
}
