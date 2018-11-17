<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\User\UserRepository;
use Joseki\Application\Responses\PdfResponse;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;

/**
 * Presenter pro generování vstupenek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TicketPresenter extends ExportBasePresenter
{
    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;


    /**
     * Vygeneruje vstupenku v PDF.
     * @throws \Exception
     * @throws AbortException
     */
    public function actionPdf() : void
    {
        if (! $this->user->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }

        $user = $this->userRepository->findById($this->user->id);

        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/templates/Ticket/pdf.latte');

        $pdf = new PdfResponse($template);

        $pdf->documentTitle =  'ticket-' . $user->getId();
        $pdf->pageFormat = 'A4';

        $this->sendResponse($pdf);
    }
}
