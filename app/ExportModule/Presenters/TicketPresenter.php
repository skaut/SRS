<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Queries\UserAttendsProgramsQuery;
use App\Model\User\Repositories\UserRepository;
use App\Services\ISettingsService;
use App\Services\QueryBus;
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
    public ISettingsService $settingsService;

    /** @inject */
    public SubeventRepository $subeventRepository;

    /** @inject */
    public UserRepository $userRepository;

    /** @inject */
    public QueryBus $queryBus;

    /**
     * Vygeneruje vstupenku v PDF.
     *
     * @throws AbortException
     * @throws SettingsException
     * @throws Throwable
     */
    public function actionPdf(): void
    {
        if (! $this->user->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }

        $user         = $this->userRepository->findById($this->user->id);
        $userPrograms = $this->queryBus->handle(new UserAttendsProgramsQuery($user));

        /** @var Template $template */
        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/templates/Ticket/pdf.latte');

        $template->logo                    = $this->settingsService->getValue(Settings::LOGO);
        $template->seminarName             = $this->settingsService->getValue(Settings::SEMINAR_NAME);
        $template->ticketUser              = $user;
        $template->ticketUserPrograms      = $userPrograms;
        $template->explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $this->pdfResponse->setTemplate($template);

        $this->pdfResponse->documentTitle = 'vstupenka';
        $this->pdfResponse->pageFormat    = 'A4';
        $this->pdfResponse->getMPDF()->SetProtection(['copy', 'print', 'print-highres'], '', random_bytes(30));

        $this->sendResponse($this->pdfResponse);
    }
}
