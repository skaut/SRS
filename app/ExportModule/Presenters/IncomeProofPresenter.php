<?php

declare(strict_types=1);

namespace App\ExportModule\Presenters;

use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\User\Application\Application;
use App\Model\User\Application\ApplicationRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use App\Services\SettingsService;
use App\Utils\Helpers;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Joseki\Application\Responses\PdfResponse;
use Nette\Application\AbortException;
use Nette\Application\ForbiddenRequestException;
use Nette\Bridges\ApplicationLatte\Template;
use Throwable;
use function random_bytes;

/**
 * Presenter pro generování dokladů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class IncomeProofPresenter extends ExportBasePresenter
{
    /** @inject */
    public ApplicationService $applicationService;

    /** @inject */
    public ApplicationRepository $applicationRepository;

    /** @inject */
    public UserRepository $userRepository;

    /** @inject */
    public SettingsService $settingsService;

    /**
     * @throws ForbiddenRequestException
     */
    public function startup() : void
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
    public function actionApplication(int $id) : void
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
    public function actionApplications() : void
    {
        $ids          = $this->session->getSection('srs')->applicationIds;
        $applications = $this->applicationRepository->findApplicationsByIds($ids)->filter(static function (Application $application) {
            return $application->getState() === ApplicationState::PAID && $application->isValid();
        });

        $this->generateIncomeProofs($applications);
    }

    /**
     * Vygeneruje doklady pro uživatele.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function actionUsers() : void
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
     * @param Collection|Application[] $applications
     *
     * @throws AbortException
     * @throws SettingsException
     * @throws Throwable
     * @throws NonUniqueResultException
     */
    private function generateIncomeProofs(Collection $applications) : void
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

        /** @var Template $template */
        $template = $this->createTemplate();
        $template->setFile(__DIR__ . '/templates/IncomeProof/pdf.latte');

        $template->applications      = $updatedApplications;
        $template->logo              = $this->settingsService->getValue(Settings::LOGO);
        $template->seminarName       = $this->settingsService->getValue(Settings::SEMINAR_NAME);
        $template->company           = $this->settingsService->getValue(Settings::COMPANY);
        $template->ico               = $this->settingsService->getValue(Settings::ICO);
        $template->accountNumber     = $this->settingsService->getValue(Settings::ACCOUNT_NUMBER);
        $template->accountant        = $this->settingsService->getValue(Settings::ACCOUNTANT);
        $template->date              = (new DateTimeImmutable())->format(Helpers::DATE_FORMAT);
        $template->paymentMethodCash = PaymentType::CASH;
        $template->paymentMethodBank = PaymentType::BANK;

        $pdf = new PdfResponse($template);

        $pdf->documentTitle = 'potvrzeni-platby';
        $pdf->pageFormat    = 'A4';
        $pdf->getMPDF()->SetProtection(['copy', 'print', 'print-highres'], '', random_bytes(30));

        $this->sendResponse($pdf);
    }
}
