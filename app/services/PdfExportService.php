<?php

namespace App\Services;

use App\Model\Enums\ApplicationState;
use App\Model\Enums\PaymentType;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsRepository;
use App\Model\User\Application;
use App\Model\User\ApplicationRepository;
use App\Model\User\User;
use fpdi\FPDI;
use Nette;


/**
 * Služba pro export do formátu PDF.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PdfExportService extends Nette\Object
{
    /** @var string */
    private $dir;

    /** @var \fpdi\FPDI */
    private $fpdi;

    private $template;

    /** @var SettingsRepository */
    private $settingsRepository;

    /** @var ApplicationRepository */
    private $applicationRepository;


    /**
     * PdfExportService constructor.
     * @param string $dir
     * @param SettingsRepository $settingsRepository
     * @param ApplicationRepository $applicationRepository
     */
    public function __construct($dir, SettingsRepository $settingsRepository, ApplicationRepository $applicationRepository)
    {
        $this->dir = $dir;

        $this->settingsRepository = $settingsRepository;
        $this->applicationRepository = $applicationRepository;

        $this->fpdi = new FPDI();
        $this->fpdi->fontpath = $dir . '/fonts/';
        $this->fpdi->AddFont('verdana', '', 'verdana.php');
        $this->fpdi->SetFont('verdana', '', 10);
    }

    /**
     * Vygeneruje doklad o zaplacení pro přihlášku.
     * @param Application $application
     * @param $filename
     */
    public function generateApplicationsPaymentProof(Application $application, $filename)
    {
        $this->prepareApplicationsPaymentProof($application);
        $this->fpdi->Output($filename, 'D');
        exit;
    }

    private function prepareApplicationsPaymentProof(Application $application)
    {
        if ($application->getState() == ApplicationState::PAID) {
            if ($application->getPaymentMethod() == PaymentType::BANK)
                $this->addAccountProofPage($application);
            else if ($application->getPaymentMethod() == PaymentType::CASH)
                $this->addIncomeProofPage($application);

            if (!$application->getIncomeProofPrintedDate() && $application->getPaymentDate()) {
                $application->setIncomeProofPrintedDate(new \DateTime());
                $this->applicationRepository->save($application);
            }
        }
    }

    /**
     * Vygeneruje doklady o zaplacení pro uživatele.
     * @param User $user
     * @param $filename
     */
    public function generateUsersPaymentProof(User $user, $filename)
    {
        $this->prepareUsersPaymentProof($user);
        $this->fpdi->Output($filename, 'D');
        exit;
    }
    
    private function prepareUsersPaymentProof(User $user)
    {
        foreach ($user->getApplications() as $application) {
            $this->prepareApplicationsPaymentProof($application);
        }
    }
    
    /**
     * Vygeneruje doklady o zaplacení pro více uživatelů.
     * @param $users
     * @param $filename
     */
    public function generateUsersPaymentProofs($users, $filename)
    {
        $this->prepareUsersPaymentProofs($users);
        $this->fpdi->Output($filename, 'D');
        exit;
    }

    private function prepareUsersPaymentProofs($users)
    {
        foreach ($users as $user) {
            $this->prepareUsersPaymentProof($user);
        }
    }

    /**
     * Vytvoří stránku s příjmovýchm dokladem.
     * @param Application $application
     * @throws \App\Model\Settings\SettingsException
     */
    private function addIncomeProofPage(Application $application)
    {
        $this->configureForIncomeProof();

        $this->fpdi->addPage();

        $this->fpdi->useTemplate($this->template, 0, 0);

        $this->fpdi->SetY(49);
        $this->fpdi->SetX(37);

        $this->fpdi->Line(135, 54, 175, 54);
        $this->fpdi->Line(135, 64, 175, 64);

        $this->fpdi->Text(133, 41, iconv('UTF-8', 'WINDOWS-1250', $application->getPaymentDate()->format("j. n. Y")));

        $this->fpdi->MultiCell(68, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue(Settings::COMPANY)));
        $this->fpdi->Text(35, 71, iconv('UTF-8', 'WINDOWS-1250', $this->settingsRepository->getValue(Settings::ICO)));
        $this->fpdi->Text(35, 77, iconv('UTF-8', 'WINDOWS-1250', '---------------')); //DIC
        $this->fpdi->Text(140, 76, iconv('UTF-8', 'WINDOWS-1250', '== ' . $application->getFee() . ' =='));
        $this->fpdi->Text(38, 86, iconv('UTF-8', 'WINDOWS-1250', '== ' . $application->getFeeWords() . ' =='));

        $this->fpdi->Text(40, 98, iconv('UTF-8', 'WINDOWS-1250',
            "{$application->getUser()->getFirstName()} {$application->getUser()->getLastName()}, {$application->getUser()->getStreet()}, {$application->getUser()->getCity()}, {$application->getUser()->getPostcode()}"));

        $this->fpdi->Text(40, 111, iconv('UTF-8', 'WINDOWS-1250', "účastnický poplatek {$this->settingsRepository->getValue(Settings::SEMINAR_NAME)}"));
    }
    
    /**
     * Vytvoří stránku s potvrzením o přijetí platby.
     * @param Application $application
     * @throws \App\Model\Settings\SettingsException
     */
    private function addAccountProofPage(Application $application)
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
        $this->fpdi->Text(70, 92, iconv('UTF-8', 'WINDOWS-1250', "{$application->getUser()->getFirstName()} {$application->getUser()->getLastName()}"));
        $this->fpdi->Text(70, 99, iconv('UTF-8', 'WINDOWS-1250', "{$application->getUser()->getStreet()}, {$application->getUser()->getCity()}, {$application->getUser()->getPostcode()}"));

        $this->fpdi->Text(31, 111, iconv('UTF-8', 'WINDOWS-1250', "{$this->settingsRepository->getValue(Settings::PRINT_LOCATION)}"));
        $this->fpdi->Text(75, 111, iconv('UTF-8', 'WINDOWS-1250', "{$this->writeToday()}"));

        $this->fpdi->Text(130, 119, iconv('UTF-8', 'WINDOWS-1250', "{$this->settingsRepository->getValue(Settings::ACCOUNTANT)}"));
    }

    /**
     * Nastaví šablonu pro příjmový doklad.
     */
    private function configureForIncomeProof()
    {
        $pagecount = $this->fpdi->setSourceFile($this->dir . '/prijmovy-pokladni-doklad.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }
    
    /**
     * Nastaví šablonu pro potvrzení o přijetí platby.
     */
    private function configureForAccountProof()
    {
        $pagecount = $this->fpdi->setSourceFile($this->dir . '/potvrzeni-o-prijeti-platby.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }

    /**
     * Vygeneruje dnešní datum.
     * @return string
     */
    private function writeToday()
    {
        $today = new \DateTime('now');
        return $today->format("j. n. Y");
    }
}
