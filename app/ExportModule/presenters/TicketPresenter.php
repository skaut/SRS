<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Model\Structure\SubeventRepository;
use App\Model\User\UserRepository;
use Joseki\Application\Responses\PdfResponse;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use function random_bytes;

/**
 * Presenter pro generování vstupenek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TicketPresenter extends ExportBasePresenter
{
    /**
     * @var    UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var    SettingsFacade
     * @inject
     */
    public $settingsFacade;

    /**
     * @var    SubeventRepository
     * @inject
     */
    public $subeventRepository;

    /**
     * Vygeneruje vstupenku v PDF.
     *
     * @throws AbortException
     * @throws SettingsException
     * @throws \Throwable
     */
    public function actionPdf() : void
    {
        if (! $this->user->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }

        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/templates/Ticket/pdf.latte');

        $template->logo                    = $this->settingsFacade->getValue(Settings::LOGO);
        $template->seminarName             = $this->settingsFacade->getValue(Settings::SEMINAR_NAME);
        $template->ticketUser              = $this->userRepository->findById($this->user->id);
        $template->explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $pdf = new PdfResponse($template);

        $pdf->documentTitle = 'ticket';
        $pdf->pageFormat    = 'A4';

        $pdf->getMPDF()->SetProtection(['copy', 'print', 'print-highres'], '', random_bytes(30));

        $this->sendResponse($pdf);
    }
}
