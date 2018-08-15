<?php
declare(strict_types=1);

namespace App\Services;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Skautis\Wsdl\WsdlException;


/**
 * Služba pro správu obecné skautIS akce.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsEventGeneralService extends SkautIsEventService
{
    /**
     * @param $eventId
     * @return bool
     */
    public function isEventDraft($eventId)
    {
        return $this->getEventDetail($eventId)->ID_EventGeneralState == 'draft';
    }

    /**
     * Vloží účastníky do skautIS.
     * @param int $eventId
     * @param Collection|User[] $users
     * @param bool $accept
     * @return bool
     */
    public function insertParticipants(int $eventId, Collection $users, bool $accept = FALSE): bool
    {
        try {
            $participants = [];

            foreach ($this->getAllParticipants($eventId) as $participant) {
                $participants[$participant->ID_Person] = TRUE;
            }

            foreach ($users as $user) {
                $personId = $user->getSkautISPersonId();

                if (!array_key_exists($personId, $participants)) {
                    $this->insertParticipant($eventId, $personId);
                }
            }
        } catch (WsdlException $e) {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * @param $eventId
     * @return mixed
     */
    protected function getEventDetail($eventId)
    {
        return $this->skautIs->event->EventGeneralDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $eventId
        ]);
    }

    /**
     * @return mixed
     */
    protected function getDraftEvents()
    {
        return $this->skautIs->event->EventGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneralState' => 'draft'
        ]);
    }

    /**
     * Vrací účastníky akce.
     * @param $eventId
     * @return mixed
     */
    private function getAllParticipants($eventId)
    {
        return $this->skautIs->event->ParticipantGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId
        ]);
    }

    /**
     * Přidá účastníka akce.
     * @param $eventId
     * @param $personId
     */
    private function insertParticipant(int $eventId, int $personId)
    {
        $this->skautIs->event->ParticipantGeneralInsert([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId,
            'ID_Person' => $personId
        ]);
    }
}
