<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Queries\UserAttendsProgramsQuery;
use App\Model\User\Repositories\UserRepository;
use App\Services\QueryBus;
use Contributte\PdfResponse\PdfResponse;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use Nette\Bridges\ApplicationLatte\Template;
use Throwable;

use function assert;
use function bin2hex;
use function random_bytes;

/**
 * Presenter pro generování vstupenek.
 */
class TicketPresenter extends ExportBasePresenter
{
    /** @inject */
    public PdfResponse $pdfResponse;

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
     * @throws Throwable
     */
    public function actionPdf(): void
    {
        if (! $this->user->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }

        $user         = $this->userRepository->findById($this->user->id);
        $userPrograms = $this->queryBus->handle(new UserAttendsProgramsQuery($user));

        $template = $this->createTemplate();
        assert($template instanceof Template);
        $template->setFile(__DIR__ . '/templates/Ticket/pdf.latte');

        $template->logo                    = $this->queryBus->handle(new SettingStringValueQuery(Settings::LOGO));
        $template->seminarName             = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));
        $template->ticketUser              = $user;
        $template->ticketUserPrograms      = $userPrograms;
        $template->explicitSubeventsExists = $this->subeventRepository->explicitSubeventsExists();

        $this->pdfResponse->setTemplate($template);

        $this->pdfResponse->documentTitle = 'vstupenka';
        $this->pdfResponse->pageFormat    = 'A4';
        $this->pdfResponse->getMPDF()->SetProtection(['copy', 'print', 'print-highres'], '', bin2hex(random_bytes(40)));

        $this->sendResponse($this->pdfResponse);
    }
}
