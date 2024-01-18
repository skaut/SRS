<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\User\Queries\UserAttendsProgramsQuery;
use App\Model\User\Repositories\UserRepository;
use App\Services\IcalResponse;
use App\Services\QueryBus;
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\DateTime;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
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
        $calendar = new Calendar();
        $calendar->setProductIdentifier('-//Junák - český skaut//SRS//CS');

        $user         = $this->userRepository->findById($id);
        $userPrograms = $this->queryBus->handle(new UserAttendsProgramsQuery($user));

        foreach ($userPrograms as $program) {
            $start = new DateTime($program->getStart(), true);
            $end   = new DateTime($program->getEnd(), true);

            $event = new Event();
            $event->setSummary($program->getBlock()->getName())
                ->setDescription($program->getBlock()->getDescription())
                ->setOccurrence(new TimeSpan($start, $end));

            // organizátor může být jen e-mail
            // if (! $program->getBlock()->getLectors()->isEmpty()) {
            //   $event->setOrganizer(new Organizer(new EmailAddress($program->getBlock()->getLectorsText())));
            // }

            if ($program->getRoom() !== null) {
                $event->setLocation(new Location($program->getRoom()->getName()));
            }

            $calendar->addEvent($event);
        }

        $icalResponse = new IcalResponse($calendar, 'harmonogram.ics');
        $this->sendResponse($icalResponse);
    }
}
