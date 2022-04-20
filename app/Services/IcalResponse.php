<?php

declare(strict_types=1);

namespace App\Services;

use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Presentation\Factory\CalendarFactory;
use Nette;
use Nette\Application\Response;

/**
 * IcalResponse
 */
class IcalResponse implements Response
{
    use Nette\SmartObject;

    public function __construct(private Calendar $calendar, private string $filename)
    {
    }

    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $componentFactory  = new CalendarFactory();
        $calendarComponent = $componentFactory->createCalendar($this->calendar);
        $httpResponse->setContentType('text/calendar', 'utf-8');
        $httpResponse->setHeader('Content-Disposition', 'attachment;filename=' . $this->filename);

        echo $calendarComponent;
    }
}
