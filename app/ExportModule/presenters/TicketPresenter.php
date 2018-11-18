<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
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
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @var SubeventRepository
     * @inject
     */
    public $subeventRepository;


    /**
     * Vygeneruje vstupenku v PDF.
     * @throws AbortException
     * @throws \App\Model\Settings\SettingsException
     * @throws \Throwable
     */
    public function actionPdf() : void
    {
        if (! $this->user->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }

        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/templates/Ticket/pdf.latte');

        $template->logo = $this->settingsRepository->getValue(Settings::LOGO);
        $template->seminarName = $this->settingsRepository->getValue(Settings::SEMINAR_NAME);
        $template->ticketUser = $this->userRepository->findById($this->user->id);
        $template->explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $pdf = new PdfResponse($template);

        $pdf->documentTitle =  'ticket';
        $pdf->pageFormat = 'A4';

        $this->sendResponse($pdf);
    }
}
