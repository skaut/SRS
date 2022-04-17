<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\User\Queries\UserAttendsProgramsQuery;
use App\Model\User\Repositories\UserRepository;
use App\Services\IcalResponse;
use App\Services\QueryBus;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;
use Exception;
use Nette\Application\AbortException;
use Nette\DI\Attributes\Inject;

/**
 * Presenter pro generování kalendáře ve formátu ICS.
 */
class SchedulePresenter extends ExportBasePresenter
{
    #[Inject]
    public UserRepository $userRepository;

    #[Inject]
    public QueryBus $queryBus;

    /**
     * @throws AbortException
     * @throws Exception
     */
    public function actionIcal(int $id): void
    {
        $calendar = new Calendar('-//Junák - český skaut//SRS//CS');

        $user         = $this->userRepository->findById($id);
        $userPrograms = $this->queryBus->handle(new UserAttendsProgramsQuery($user));

        foreach ($userPrograms as $program) {
            $event = new Event();
            $event->setDtStart($program->getStart())
                ->setDtEnd($program->getEnd())
                ->setSummary($program->getBlock()->getName())
                ->setDescription($program->getBlock()->getDescription());

            if (! $program->getBlock()->getLectors()->isEmpty()) {
                $event->setOrganizer(new Organizer($program->getBlock()->getLectorsText()));
            }

            if ($program->getRoom() !== null) {
                $event->setLocation($program->getRoom()->getName());
            }

            $calendar->addComponent($event);
        }

        $icalResponse = new IcalResponse($calendar, 'harmonogram.ics');
        $this->sendResponse($icalResponse);
    }
}
