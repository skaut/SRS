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
use Throwable;
use function random_bytes;

/**
 * Presenter pro generování vstupenek.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TicketPresenter extends ExportBasePresenter
{
    /** @inject */
    public PdfResponse $pdfResponse;

    /** @inject */
    public SettingsService $settingsService;

    /** @inject */
    public SubeventRepository $subeventRepository;

    /** @inject */
    public UserRepository $userRepository;

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

        $this->pdfResponse->setTemplate($template);

        $this->pdfResponse->documentTitle = 'vstupenka';
        $this->pdfResponse->pageFormat    = 'A4';
        $this->pdfResponse->getMPDF()->SetProtection(['copy', 'print', 'print-highres'], '', random_bytes(30));

        $this->sendResponse($this->pdfResponse);
    }
}
