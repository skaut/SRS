<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use App\Model\User\UserRepository;
use App\Services\SettingsService;
use Joseki\Application\Responses\PdfResponse;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Http\Response;
use Throwable;
use function random_bytes;

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
     * @var SettingsService
     * @inject
     */
    public $settingsService;

    /**
     * @var SubeventRepository
     * @inject
     */
    public $subeventRepository;

    /**
     * Vygeneruje vstupenku v PDF.
     *
     * @throws AbortException
     * @throws SettingsException
     * @throws Throwable
     */
    public function actionPdf() : void
    {
        if (! $this->user->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }

        /** @var Template $template */
        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/templates/Ticket/pdf.latte');

        $template->logo                    = $this->settingsService->getValue(Settings::LOGO);
        $template->seminarName             = $this->settingsService->getValue(Settings::SEMINAR_NAME);
        $template->ticketUser              = $this->userRepository->findById($this->user->id);
        $template->explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $pdf = new PdfResponse($template);

        $pdf->documentTitle = 'ticket';
        $pdf->pageFormat    = 'A4';
        $pdf->getMPDF()->SetProtection(['copy', 'print', 'print-highres'], '', random_bytes(30));
        $pdf->setSaveMode(PdfResponse::INLINE);
        $this->sendResponse($pdf);
    }
}
