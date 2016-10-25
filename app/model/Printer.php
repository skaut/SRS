<?php
/**
 * Date: 20.2.13
 * Time: 16:21
 * Author: Michal Májský
 */

namespace SRS\Model;

/**
 * Obsluhuje tisk dokladu
 */
class Printer extends \Nette\Object
{
    protected $fpdi;

    protected $template;

    protected $dbsettings;


    public function __construct($em)
    {
        $this->dbsettings = $em->getRepository('\SRS\Model\Settings');
        $this->fpdi = new \fpdi\FPDI();
        $this->fpdi->fontpath = LOCAL_LIBS_DIR . '/fonts/';
        $this->fpdi->AddFont('verdana', '', 'verdana.php');
        $this->fpdi->SetFont('verdana', '', 10);
    }

    public function printPaymentProofs($users)
    {
        foreach ($users as $user) {
            if ($user->paymentMethod == 'bank')
                $this->addAccountProof($user);
            else if ($user->paymentMethod == 'cash')
                $this->addIncomeProof($user);
        }
        $this->fpdi->Output('doklady_o_zaplaceni.pdf', 'D');
        exit;
    }

    protected function addIncomeProof(\SRS\Model\User $user)
    {
        $this->configureForIncomeProof();

        $this->fpdi->addPage();

        $this->fpdi->useTemplate($this->template, 0, 0);

        $this->fpdi->SetY(49);
        $this->fpdi->SetX(37);

        $this->fpdi->Line(135, 54, 175, 54);
        $this->fpdi->Line(135, 64, 175, 64);

        $this->fpdi->Text(133, 41, iconv('UTF-8', 'WINDOWS-1250', $user->paymentDate->format("d.m.Y")));

        $this->fpdi->MultiCell(68, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('company')));
        $this->fpdi->Text(35, 71, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('ico')));
        $this->fpdi->Text(35, 77, iconv('UTF-8', 'WINDOWS-1250', '---------------')); //dic
        $this->fpdi->Text(140, 76, iconv('UTF-8', 'WINDOWS-1250', '== ' . $user->countFee()['fee'] . ' =='));
        $this->fpdi->Text(38, 86, iconv('UTF-8', 'WINDOWS-1250', '== ' . $user->countFee()['feeWord'] . ' =='));

        $this->fpdi->Text(40, 98, iconv('UTF-8', 'WINDOWS-1250', "{$user->firstName} {$user->lastName}, {$user->street}, {$user->city}, {$user->postcode}"));

        $this->fpdi->Text(40, 111, iconv('UTF-8', 'WINDOWS-1250', "účastnický poplatek {$this->dbsettings->get('seminar_name')}"));
    }

    protected function addAccountProof(\SRS\Model\User $user)
    {
        $this->configurForAccountProof();

        $this->fpdi->addPage();
        $this->fpdi->useTemplate($this->template, 0, 0);
        $this->fpdi->SetY(30);
        $this->fpdi->SetX(25);
        $this->fpdi->MultiCell(68, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('company')));
        $this->fpdi->Text(26, 52, iconv('UTF-8', 'WINDOWS-1250', 'IČO: ' . $this->dbsettings->get('ico')));

        $this->fpdi->Text(70, 71, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('account_number')));

        $this->fpdi->Text(70, 78, iconv('UTF-8', 'WINDOWS-1250', $user->countFee()['fee'] . ' Kč, slovy =' . $user->countFee()['feeWord'] . '='));
        $this->fpdi->Text(70, 85, iconv('UTF-8', 'WINDOWS-1250', 'účastnický poplatek ' . $this->dbsettings->get('seminar_name')));
        $this->fpdi->Text(70, 92, iconv('UTF-8', 'WINDOWS-1250', "{$user->firstName} {$user->lastName}"));
        $this->fpdi->Text(70, 99, iconv('UTF-8', 'WINDOWS-1250', "{$user->street}, {$user->city}, {$user->postcode}"));

        $this->fpdi->Text(31, 111, iconv('UTF-8', 'WINDOWS-1250', "{$this->dbsettings->get('print_location')}"));
        $this->fpdi->Text(75, 111, iconv('UTF-8', 'WINDOWS-1250', "{$this->writeToday()}"));

        $this->fpdi->Text(130, 119, iconv('UTF-8', 'WINDOWS-1250', "{$this->dbsettings->get('accountant')}"));
    }

    protected function configureForIncomeProof()
    {
        $pagecount = $this->fpdi->setSourceFile(WWW_DIR . '/print/pokladni-prijmovy-doklad.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }


    protected function configurForAccountProof()
    {
        $pagecount = $this->fpdi->setSourceFile(WWW_DIR . '/print/potvrzeni-o-prijeti-platby.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }

    protected function writeToday()
    {
        $today = new \DateTime('now');
        return $today->format("d.m.Y");
    }
}
