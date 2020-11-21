<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\User\UserRepository;
use App\Services\IcalResponse;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Eluceo\iCal\Property\Event\Organizer;
use Exception;
use Nette\Application\AbortException;

/**
 * Presenter pro generování kalendáře ve formátu ICS.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SchedulePresenter extends ExportBasePresenter
{
    /** @inject */
    public UserRepository $userRepository;

    /**
     * @throws AbortException
     * @throws Exception
     */
    public function actionIcal(int $id) : void
    {
        $calendar = new Calendar('-//Junák - český skaut//SRS//CS');

        $user     = $this->userRepository->findById($id);
        $programs = $user->getPrograms();

        foreach ($programs as $program) {
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
