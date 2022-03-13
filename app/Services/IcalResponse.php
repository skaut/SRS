<?php

declare(strict_types=1);

namespace App\Services;

use Eluceo\iCal\Component\Calendar;
use Nette;
use Nette\Application\Response;

/**
 * IcalResponse.
 */
class IcalResponse implements Response
{
    use Nette\SmartObject;

    private Calendar $calendar;

    private string $filename;

    public function __construct(Calendar $calendar, string $filename)
    {
        $this->calendar = $calendar;
        $this->filename = $filename;
    }

    public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void
    {
        $httpResponse->setContentType('text/calendar', 'utf-8');
        $httpResponse->setHeader('Content-Disposition', 'attachment;filename=' . $this->filename);

        echo $this->calendar->render();
    }
}
