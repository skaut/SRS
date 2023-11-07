<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Skaut\Skautis\Wsdl\WsdlException;
use stdClass;
use Tracy\Debugger;
use Tracy\ILogger;

use function array_key_exists;
use function print_r;
use function sprintf;

/**
 * Služba pro správu obecné skautIS akce.
 */
class SkautIsEventGeneralService extends SkautIsEventService
{
    public function isEventDraft(int $eventId): bool
    {
        return $this->getEventDetail($eventId)->ID_EventGeneralState === 'draft';
    }

    /**
     * Vloží účastníky do skautIS.
     *
     * @param Collection<int, User> $users
     */
    public function insertParticipants(int $eventId, Collection $users, bool $accept = false): bool
    {
        try {
            $participants = [];

            foreach ($this->getAllParticipants($eventId) as $participant) {
                $participants[$participant->ID_Person] = true;
            }

            foreach ($users as $user) {
                $personId = $user->getSkautISPersonId();

                if (! array_key_exists($personId, $participants)) {
                    $this->insertParticipant($eventId, $personId);
                }
            }
        } catch (WsdlException $ex) {
            Debugger::log($ex, ILogger::WARNING);

            return false;
        }

        return true;
    }

    protected function getEventDetail(int $eventId): stdClass
    {
        return $this->skautIs->event->EventGeneralDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $eventId,
        ]);
    }

    /** @return stdClass[] */
    protected function getDraftEvents(): array
    {
        $response = $this->skautIs->event->EventGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneralState' => 'draft',
        ]);

        return $response instanceof stdClass ? [] : $response;
    }

    /**
     * Vrací účastníky akce.
     *
     * @return stdClass[]
     */
    private function getAllParticipants(int $eventId): array
    {
        Debugger::log(sprintf('Calling ParticipantGeneralAll for ID_EventGeneral: %d.', $eventId));

        $response = $this->skautIs->event->ParticipantGeneralAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId,
        ]);

        $response = $response instanceof stdClass ? [] : $response;

        Debugger::log(sprintf('ParticipantGeneralAll done, response: %s.', print_r($response, true)));

        return $response;
    }

    /**
     * Přidá účastníka akce.
     */
    private function insertParticipant(int $eventId, int $personId): void
    {
        Debugger::log(sprintf(
            'Calling ParticipantGeneralInsert for ID_EventGeneral: %d, ID_Person: %d.',
            $eventId,
            $personId,
        ));

        $this->skautIs->event->ParticipantGeneralInsert([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventGeneral' => $eventId,
            'ID_Person' => $personId,
        ]);

        Debugger::log('ParticipantGeneralInsert done.');
    }
}
