<?php
declare(strict_types=1);

namespace App\Services;
use App\Model\SkautIs\SkautIsCourseRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\User;
use Doctrine\Common\Collections\Collection;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;


/**
 * Služba pro správu vzdělávací skautIS akce.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SkautIsEventEducationService extends SkautIsEventService
{
    /** @var SkautIsCourseRepository */
    private $skautIsCourseRepository;

    /** @var SubeventRepository */
    private $subeventRepository;


    /**
     * SkautIsEventEducationService constructor.
     * @param Skautis $skautIs
     * @param SkautIsCourseRepository $skautIsCourseRepository
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(Skautis $skautIs, SkautIsCourseRepository $skautIsCourseRepository,
                                SubeventRepository $subeventRepository)
    {
        parent::__construct($skautIs);

        $this->skautIsCourseRepository = $skautIsCourseRepository;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * @param $eventId
     * @return bool
     */
    public function isEventDraft($eventId)
    {
        return $this->getEventDetail($eventId)->ID_EventEducationState == 'draft';
    }

    /**
     * @param int $eventId
     * @param Collection|User[] $users
     * @param bool $accept
     * @return bool
     */
    public function insertParticipants(int $eventId, Collection $users, bool $accept = FALSE): bool
    {
        try {
            $participants = [];

            foreach ($this->skautIsCourseRepository->findAll() as $course) {
                $courseId = $course->getSkautIsCourseId();
                $participants[$courseId] = [];

                foreach ($this->getAllParticipants($eventId, $courseId) as $participant) {
                    $participants[$courseId][$participant->ID_Person] = TRUE;
                }
            }

            foreach ($users as $user) {
                $personId = $user->getSkautISPersonId();

                foreach ($user->getSubevents() as $subevent) {
                    foreach ($subevent->getSkautIsCourses() as $course) {
                        $courseId = $course->getSkautIsCourseId();

                        if (!array_key_exists($personId, $participants[$courseId])) {
                            $this->insertParticipant($eventId, $course->getSkautIsCourseId(), $personId, $accept);
                            $participants[$courseId][$personId] = TRUE;
                        }
                    }
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
        return $this->skautIs->event->EventEducationDetail([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID' => $eventId
        ]);
    }

    /**
     * @return mixed
     */
    protected function getDraftEvents()
    {
        //TODO vracet jen akce, kam je možné přidávat účastníky
        return $this->skautIs->event->EventEducationAllMyActions([
            'ID_Login' => $this->skautIs->getUser()->getLoginId()
        ]);
//        return $this->skautIs->event->EventEducationAllMyActions([
//            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
//            'ID_EventEducationState' => 'draft'
//        ]);
    }

    /**
     * Vrací kurzy vzdělavací akce.
     * @param int $eventId
     * @return mixed
     */
    public function getEventCourses(int $eventId)
    {
        return $this->skautIs->event->EventEducationCourseAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducation' => $eventId
        ]);
    }

    /**
     * Je nastaveno propojení alespoň jedné podakce se skautIS kurzem?
     * @return bool
     */
    public function isSubeventConnected()
    {
        foreach ($this->subeventRepository->findAll() as $subevent) {
            if (!$subevent->getSkautIsCourses()->isEmpty())
                return TRUE;
        }
        return FALSE;
    }

    /**
     * Vrací přihlášené účastníky kurzu.
     * @param $eventId
     * @param $courseId
     * @return mixed
     */
    private function getAllParticipants($eventId, $courseId)
    {
        return $this->skautIs->event->ParticipantEducationAll([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducation' => $eventId,
            'ID_EventEducationCourse' => [$courseId, $courseId],
            'IsActive' => TRUE
        ]);
    }

    /**
     * Přidá účastníka kurzu.
     * @param $eventId
     * @param $courseId
     * @param $personId
     * @param bool $accept
     */
    private function insertParticipant($eventId, $courseId, $personId, bool $accept)
    {
        $response = $this->skautIs->event->ParticipantEducationInsert([
            'ID_Login' => $this->skautIs->getUser()->getLoginId(),
            'ID_EventEducation' => $eventId,
            'ID_EventEducationCourse' => $courseId,
            'ID_Person' => $personId
        ]);

        if ($accept) {
            $this->skautIs->event->ParticipantEducationUpdate([
                'ID_Login' => $this->skautIs->getUser()->getLoginId(),
                'ID' => $response->ID,
                'IsAccepted' => $accept
            ], 'participantEducation');
        }
    }
}
