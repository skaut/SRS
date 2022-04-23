<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\SkautIs\Repositories\SkautIsCourseRepository;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use stdClass;
use Tracy\Debugger;
use Tracy\ILogger;

use function array_key_exists;
use function print_r;
use function sprintf;

/**
 * Služba pro správu vzdělávací skautIS akce.
 */
class SkautIsEventEducationService extends SkautIsEventService
{
    public function __construct(
        Skautis $skautIs,
        private SkautIsCourseRepository $skautIsCourseRepository,
        private SubeventRepository $subeventRepository
    ) {
        parent::__construct($skautIs);
    }

    public function isEventDraft(int $eventId): bool
    {
        return true;

//        return $this->getEventDetail($eventId)->ID_EventEducationState === 'draft';
    }

    /**
     * @param Collection<int, User> $users
     */
    public function insertParticipants(int $eventId, Collection $users, bool $accept = false): bool
    {
        try {
            $participants = [];

            foreach ($this->skautIsCourseRepository->findAll() as $course) {
                $courseId                = $course->getSkautIsCourseId();
                $participants[$courseId] = [];

                foreach ($this->getAllParticipants($eventId, $courseId) as $participant) {
                    $participants[$courseId][$participant->ID_Person] = ['id' => $participant->ID, 'accepted' => $participant->IsAccepted];
                }
            }

            foreach ($users as $user) {
                $personId = $user->getSkautISPersonId();

                foreach ($user->getSubevents() as $subevent) {
                    foreach ($subevent->getSkautIsCourses() as $course) {
                        $courseId = $course->getSkautIsCourseId();

                        if (! array_key_exists($personId, $participants[$courseId])) {
                            $participantId                      = $this->insertParticipant($eventId, $course->getSkautIsCourseId(), $personId);
                            $participants[$courseId][$personId] = ['id' => $participantId, 'accepted' => false];
                        }

                        if ($participants[$courseId][$personId]['accepted'] !== $accept) {
                            $this->updateParticipant($participants[$courseId][$personId]['id'], $accept);
                            $participants[$courseId][$personId]['accepted'] = $accept;
                        }
                    }
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
        return $this->skautIs->event->EventEducationDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $eventId,
        ]);
    }

    /**
     * @return stdClass[]
     */
    protected function getDraftEvents(): array
    {
        $response = $this->skautIs->event->EventEducationAllMyActions([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
        ]);

        return $response instanceof stdClass ? [] : $response;

        // TODO: vracet jen akce, kam je možné přidávat účastníky
        //        return $this->skautIs->event->EventEducationAllMyActions([
        //            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
        //            'ID_EventEducationState' => 'draft'
        //        ]);
    }

    /**
     * Vrací kurzy vzdělávací akce.
     *
     * @return stdClass[]
     */
    public function getEventCourses(int $eventId): array
    {
        $response = $this->skautIs->event->EventEducationCourseAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducation' => $eventId,
        ]);

        return $response instanceof stdClass ? [] : $response;
    }

    /**
     * Je nastaveno propojení alespoň jedné podakce se skautIS kurzem?
     */
    public function isSubeventConnected(): bool
    {
        foreach ($this->subeventRepository->findAll() as $subevent) {
            if (! $subevent->getSkautIsCourses()->isEmpty()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vrací přihlášené účastníky kurzu.
     *
     * @return stdClass[]
     */
    private function getAllParticipants(int $eventId, int $courseId): array
    {
        Debugger::log(sprintf(
            'Calling ParticipantEducationAll for ID_EventEducation: %d, ID_EventEducationCourse: %d.',
            $eventId,
            $courseId
        ));

        $response = $this->skautIs->event->ParticipantEducationAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducation' => $eventId,
            'ID_EventEducationCourse' => [$courseId, $courseId],
            'IsActive' => true,
        ]);

        $response = $response instanceof stdClass ? [] : $response;

        Debugger::log(sprintf('ParticipantEducationAll done, response: %s.', print_r($response, true)));

        return $response;
    }

    /**
     * Přidá účastníka kurzu.
     */
    private function insertParticipant(int $eventId, int $courseId, int $personId): int
    {
        Debugger::log(sprintf(
            'Calling ParticipantEducationInsert for ID_EventEducation: %d, ID_EventEducationCourse: %d, ID_Person: %d.',
            $eventId,
            $courseId,
            $personId
        ));

        $response = $this->skautIs->event->ParticipantEducationInsert([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducation' => $eventId,
            'ID_EventEducationCourse' => $courseId,
            'ID_Person' => $personId,
        ]);

        Debugger::log(sprintf('ParticipantEducationInsert done, response: %s.', print_r($response, true)));

        return $response->ID;
    }

    /**
     * Aktualizuje přijetí účastníka.
     */
    private function updateParticipant(int $participantId, bool $accept): void
    {
        Debugger::log(sprintf('Calling ParticipantEducationUpdate for ID: %d.', $participantId));

        $this->skautIs->event->ParticipantEducationUpdate([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $participantId,
            'IsAccepted' => $accept,
        ], 'participantEducation');

        Debugger::log('ParticipantEducationUpdate done.');
    }
}
