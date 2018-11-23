<?php

declare(strict_types=1);

namespace App\Services;

use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\User;
use App\Utils\Helpers;
use Doctrine\Common\Collections\Collection;
use FPDI;
use Nette;
use ReflectionObject;
use function iconv;

/**
 * Služba pro export do formátu PDF.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PdfExportService
{
    use Nette\SmartObject;

    /** @var string */
    private $dir;

    /** @var FPDI */
    private $fpdi;

    /** @var int */
    private $template;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;

    /** @var ApplicationService */
    private $applicationService;


    public function __construct(
        string $dir,
        SettingsRepository $settingsRepository,
        ApplicationRepository $applicationRepository,
        ApplicationService $applicationService
    ) {
        $this->dir = $dir;

        $this->settingsRepository    = $settingsRepository;
        $this->applicationRepository = $applicationRepository;
        $this->applicationService    = $applicationService;

        $this->fpdi = new FPDI();

        $refFpdi         = new ReflectionObject($this->fpdi);
        $refFpdiFontpath = $refFpdi->getProperty('fontpath');
        $refFpdiFontpath->setAccessible(true);
        $refFpdiFontpath->setValue($this->fpdi, $dir . '/fonts/');

        $this->fpdi->AddFont('verdana', '', 'verdana.php');
        $this->fpdi->SetFont('verdana', '', 10);
    }

    /**
     * Vygeneruje doklad o zaplacení pro přihlášku.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function generateApplicationsPaymentProof(Application $application, string $filename, User $createdBy) : void
    {
        $this->prepareApplicationsPaymentProof($application, $createdBy);
        $this->fpdi->Output($filename, 'D');
        exit;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    private function prepareApplicationsPaymentProof(Application $application, User $createdBy) : void
    {
        if ($application->getState() !== ApplicationState::PAID) {
            return;
        }

        if ($application->getPaymentMethod() === PaymentType::BANK) {
            $this->addAccountProofPage($application);
        } elseif ($application->getPaymentMethod() === PaymentType::CASH) {
            $this->addIncomeProofPage($application);
        }

        if ($application->getIncomeProofPrintedDate()) {
            return;
        }

        $this->applicationService->updateApplicationPayment(
            $application,
            $application->getPaymentMethod(),
            $application->getPaymentDate(),
            new \DateTime(),
            $application->getMaturityDate(),
            $createdBy
        );
    }

    /**
     * @param Collection|Application[] $applications
     * @throws SettingsException
     * @throws \Throwable
     */
    public function generateApplicationsPaymentProofs(Collection $applications, string $filename, User $createdBy) : void
    {
        $this->prepareApplicationsPaymentProofs($applications, $createdBy);
        $this->fpdi->Output($filename, 'D');
        exit;
    }

    /**
     * @param Collection|Application[] $applications
     * @throws SettingsException
     * @throws \Throwable
     */
    private function prepareApplicationsPaymentProofs(Collection $applications, User $createdBy) : void
    {
        foreach ($applications as $application) {
            $this->prepareApplicationsPaymentProof($application, $createdBy);
        }
    }

    /**
     * Vygeneruje doklady o zaplacení pro uživatele.
     * @throws SettingsException
     * @throws \Throwable
     */
    public function generateUsersPaymentProof(User $user, string $filename, User $createdBy) : void
    {
        $this->prepareUsersPaymentProof($user, $createdBy);
        $this->fpdi->Output($filename, 'D');
        exit;
    }

    /**
     * @throws SettingsException
     * @throws \Throwable
     */
    private function prepareUsersPaymentProof(User $user, User $createdBy) : void
    {
        foreach ($user->getNotCanceledApplications() as $application) {
            $this->prepareApplicationsPaymentProof($application, $createdBy);
        }
    }

    /**
     * Vygeneruje doklady o zaplacení pro více uživatelů.
     * @param Collection|User[] $users
     * @throws SettingsException
     * @throws \Throwable
     */
    public function generateUsersPaymentProofs(Collection $users, string $filename, User $createdBy) : void
    {
        $this->prepareUsersPaymentProofs($users, $createdBy);
        $this->fpdi->Output($filename, 'D');
        exit;
    }

    /**
     * @param Collection|User[] $users
     * @throws SettingsException
     * @throws \Throwable
     */
    private function prepareUsersPaymentProofs(Collection $users, User $createdBy) : void
    {
        foreach ($users as $user) {
            $this->prepareUsersPaymentProof($user, $createdBy);
        }
    }

    /**
     * Vytvoří stránku s příjmovýchm dokladem.
     * @throws SettingsException
     * @throws \Throwable
     */
    private function addIncomeProofPage(Application $application) : void
    {
        $this->configureForIncomeProof();

        $this->fpdi->addPage();

        $this->fpdi->useTemplate($this->template, 0, 0);

        $this->fpdi->SetY(49);
        $this->fpdi->SetX(37);

        $this->fpdi->Line(135, 54, 175, 54);
        $this->fpdi->Line(135, 64, 175, 64);

        $this->fpdi->Text(133, 41, iconv('UTF-8', 'WINDOWS-1250', $application->getPaymentDate()->format(Helpers::DATE_FORMAT)));

        $this->fpdi->MultiCell(68, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue(Settings::COMPANY)));
        $this->fpdi->Text(35, 71, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue(Settings::ICO)));
        $this->fpdi->Text(35, 77, iconv('UTF-8', 'WINDOWS-1250', '---------------')); //DIC
        $this->fpdi->Text(140, 76, iconv('UTF-8', 'WINDOWS-1250', '== ' . $application->getFee() . ' =='));
        $this->fpdi->Text(38, 86, iconv('UTF-8', 'WINDOWS-1250', '== ' . $application->getFeeWords() . ' =='));

        $this->fpdi->Text(40, 98, iconv(
            'UTF-8',
            'WINDOWS-1250',
            $application->getUser()->getFirstName() . ' ' . $application->getUser()->getLastName() . ', ' . $application->getUser()->getStreet() . ', ' . $application->getUser()->getCity() . ', ' . $application->getUser()->getPostcode()
        ));

        $this->fpdi->Text(40, 111, iconv('UTF-8', 'WINDOWS-1250', 'účastnický poplatek ' . $this->settingsRepository->getValue(Settings::SEMINAR_NAME)));
    }

    /**
     * Vytvoří stránku s potvrzením o přijetí platby.
     * @throws SettingsException
     * @throws \Throwable
     */
    private function addAccountProofPage(Application $application) : void
    {
        $this->configureForAccountProof();

        $this->fpdi->addPage();
        $this->fpdi->useTemplate($this->template, 0, 0);
        $this->fpdi->SetY(30);
        $this->fpdi->SetX(25);
        $this->fpdi->MultiCell(68, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue(Settings::COMPANY)));
        $this->fpdi->Text(26, 52, iconv('UTF-8', 'WINDOWS-1250', 'IČO: ' . $this->settingsRepository->getValue(Settings::ICO)));

        $this->fpdi->Text(70, 71, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue(Settings::ACCOUNT_NUMBER)));

        $this->fpdi->Text(70, 78, iconv('UTF-8', 'WINDOWS-1250', $application->getFee() . ' Kč, slovy =' . $application->getFeeWords() . '='));
        $this->fpdi->Text(70, 85, iconv('UTF-8', 'WINDOWS-1250', 'účastnický poplatek ' . $this->settingsRepository->getValue(Settings::SEMINAR_NAME)));
        $this->fpdi->Text(70, 92, iconv('UTF-8', 'WINDOWS-1250', $application->getUser()->getFirstName() . ' ' . $application->getUser()->getLastName()));
        $this->fpdi->Text(70, 99, iconv('UTF-8', 'WINDOWS-1250', $application->getUser()->getStreet() . ', ' . $application->getUser()->getCity() . ', ' . $application->getUser()->getPostcode()));

        $this->fpdi->Text(31, 111, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue(Settings::PRINT_LOCATION)));
        $this->fpdi->Text(75, 111, iconv('UTF-8', 'WINDOWS-1250', $this->writeToday()));

        $this->fpdi->Text(130, 119, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue(Settings::ACCOUNTANT)));
    }

    /**
     * Nastaví šablonu pro příjmový doklad.
     */
    private function configureForIncomeProof() : void
    {
        $this->fpdi->setSourceFile($this->dir . '/prijmovy-pokladni-doklad.pdf');
        $template       = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }

    /**
     * Nastaví šablonu pro potvrzení o přijetí platby.
     */
    private function configureForAccountProof() : void
    {
        $this->fpdi->setSourceFile($this->dir . '/potvrzeni-o-prijeti-platby.pdf');
        $template       = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }

    /**
     * Vygeneruje dnešní datum.
     */
    private function writeToday() : string
    {
        $today = new \DateTime('now');
        return $today->format(Helpers::DATE_FORMAT);
    }
}
