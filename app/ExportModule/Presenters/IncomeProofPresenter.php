<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\Application\Application;
use App\Model\Application\Repositories\ApplicationRepository;
use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Settings\Exceptions\SettingsItemNotFoundException;
use App\Model\Settings\Queries\SettingStringValueQuery;
use App\Model\Settings\Settings;
use App\Model\User\Repositories\UserRepository;
use App\Services\ApplicationService;
use App\Services\QueryBus;
use App\Utils\Helpers;
use Contributte\PdfResponse\PdfResponse;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\DI\Attributes\Inject;
use Throwable;

use function assert;
use function bin2hex;
use function random_bytes;

/**
 * Presenter pro generování dokladů.
 */
class IncomeProofPresenter extends ExportBasePresenter
{
    #[Inject]
    public QueryBus $queryBus;

    #[Inject]
    public ApplicationService $applicationService;

    #[Inject]
    public ApplicationRepository $applicationRepository;

    #[Inject]
    public PdfResponse $pdfResponse;

    #[Inject]
    public UserRepository $userRepository;

    /**
     * @throws ForbiddenRequestException
     */
    public function startup(): void
    {
        parent::startup();

        if (! $this->user->isLoggedIn()) {
            throw new ForbiddenRequestException();
        }
    }

    /**
     * Vygeneruje doklad pro přihlášku.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionApplication(int $id): void
    {
        $applications = new ArrayCollection();
        $application  = $this->applicationRepository->findById($id);

        if ($application->getState() === ApplicationState::PAID && $application->isValid()) {
            $applications->add($application);
        }

        $this->generateIncomeProofs($applications);
    }

    /**
     * Vygeneruje doklady pro přihlášky.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionApplications(): void
    {
        $ids          = $this->session->getSection('srs')->applicationIds;
        $applications = $this->applicationRepository->findApplicationsByIds($ids)
            ->filter(
                static fn (Application $application) => $application->getState()
                    === ApplicationState::PAID && $application->isValid()
            );

        $this->generateIncomeProofs($applications);
    }

    /**
     * Vygeneruje doklady pro uživatele.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionUsers(): void
    {
        $ids          = $this->session->getSection('srs')->userIds;
        $users        = $this->userRepository->findUsersByIds($ids);
        $applications = new ArrayCollection();

        foreach ($users as $user) {
            foreach ($user->getPaidApplications() as $application) {
                $applications->add($application);
            }
        }

        $this->generateIncomeProofs($applications);
    }

    /**
     * @param Collection<int, Application> $applications
     *
     * @throws AbortException
     * @throws SettingsItemNotFoundException
     * @throws Throwable
     * @throws NonUniqueResultException
     */
    private function generateIncomeProofs(Collection $applications): void
    {
        $createdBy           = $this->userRepository->findById($this->user->id);
        $updatedApplications = new ArrayCollection();

        foreach ($applications as $application) {
            if ($application->getIncomeProof() === null) {
                $this->applicationService->createIncomeProof($application, $createdBy);
                $updatedApplications->add($this->applicationRepository->findValidByVariableSymbol($application->getVariableSymbolText()));
            } else {
                $updatedApplications->add($application);
            }
        }

        $template = $this->createTemplate();
        assert($template instanceof Template);
        $template->setFile(__DIR__ . '/templates/IncomeProof/pdf.latte');

        $template->applications      = $updatedApplications;
        $template->logo              = $this->queryBus->handle(new SettingStringValueQuery(Settings::LOGO));
        $template->seminarName       = $this->queryBus->handle(new SettingStringValueQuery(Settings::SEMINAR_NAME));
        $template->company           = $this->queryBus->handle(new SettingStringValueQuery(Settings::COMPANY));
        $template->ico               = $this->queryBus->handle(new SettingStringValueQuery(Settings::ICO));
        $template->accountNumber     = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNT_NUMBER));
        $template->accountant        = $this->queryBus->handle(new SettingStringValueQuery(Settings::ACCOUNTANT));
        $template->date              = (new DateTimeImmutable())->format(Helpers::DATE_FORMAT);
        $template->paymentMethodCash = PaymentType::CASH;
        $template->paymentMethodBank = PaymentType::BANK;

        $this->pdfResponse->setTemplate($template);

        $this->pdfResponse->documentTitle = 'potvrzeni-platby';
        $this->pdfResponse->pageFormat    = 'A4';
        $this->pdfResponse->getMPDF()->SetProtection(['copy', 'print', 'print-highres'], '', bin2hex(random_bytes(40)));

        $this->sendResponse($this->pdfResponse);
    }
}
