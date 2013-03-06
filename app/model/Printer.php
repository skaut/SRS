<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 20.2.13
 * Time: 16:21
 * To change this template use File | Settings | File Templates.
 */

namespace SRS\Model;

class Printer extends \Nette\Object
{
    protected $fpdi;

    protected $template;

    protected $dbsettings;


    public function __construct($em)
    {
        $this->dbsettings = $em->getRepository('\SRS\Model\Settings');
        $this->fpdi = new \fpdi\FPDI();
        $this->fpdi->fontpath = LOCAL_LIBS_DIR.'/fonts/';
        $this->fpdi->AddFont('verdana', '','verdana.php');
        $this->fpdi->SetFont('verdana', '', 10);
    }

    public function printIncomeProofs($users)
    {
        $this->configureForIncomeProof();
        foreach ($users as $user) {
            $this->addIncomeProof($user);
        }
        $this->fpdi->Output('prijmove_doklady.pdf', 'D');
        exit;
    }

    public function printAccountProofs($users)
    {
        $this->configurForAccountProof();
        foreach ($users as $user) {
            $this->addAccountProof($user);
        }
        $this->fpdi->Output('potvrzeni_o_prijeti_platby.pdf', 'D');
        exit;
    }


    protected function addIncomeProof(\SRS\Model\User $user)
    {
        $this->fpdi->addPage();

        $this->fpdi->useTemplate($this->template, 0, 0);

        $this->fpdi->SetY(49);
        $this->fpdi->SetX(32);
        $this->fpdi->Line(20,36,80,36);

        $this->fpdi->Line(135,54,175,54);
        $this->fpdi->Line(135,64,175,64);

        $this->fpdi->MultiCell(100, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('company')));
        $this->fpdi->Text(35, 71, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('ico')));
        $this->fpdi->Text(35, 77, iconv('UTF-8', 'WINDOWS-1250', '---------------')); //dic
        $this->fpdi->Text(140, 76, iconv('UTF-8', 'WINDOWS-1250','== '.$user->role->fee.' =='));
        $this->fpdi->Text(38, 86, iconv('UTF-8', 'WINDOWS-1250','== '.$user->role->feeWord.' =='));

        $this->fpdi->Line(20,92,50,92);

        $this->fpdi->Text(40, 98, iconv('UTF-8', 'WINDOWS-1250', "{$user->firstName} {$user->lastName}, {$user->street}, {$user->city}, {$user->postcode}"));
        $this->fpdi->Text(40, 103, iconv('UTF-8', 'WINDOWS-1250', "jednotka"));

        $this->fpdi->Text(40, 111, iconv('UTF-8', 'WINDOWS-1250', "účastnický poplatek {$this->dbsettings->get('seminar_name')}"));

        $this->fpdi->Line(80,121,100,121);

    }

    protected function addAccountProof(\SRS\Model\User $user)
    {
        $this->fpdi->addPage();
        $this->fpdi->useTemplate($this->template, 0, 0);
        $this->fpdi->SetY(30);
        $this->fpdi->SetX(25);
        $this->fpdi->MultiCell(100, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('company')));
        $this->fpdi->Text(25, 52, iconv('UTF-8', 'WINDOWS-1250', 'IČO:'.$this->dbsettings->get('ico')));

        $this->fpdi->Text(70, 71, iconv('UTF-8', 'WINDOWS-1250', 'cislo uctu >]'));

        $this->fpdi->Text(70, 78, iconv('UTF-8', 'WINDOWS-1250', $user->role->fee . ' Kč'));
        $this->fpdi->Text(90, 78, iconv('UTF-8', 'WINDOWS-1250',', slovy ='.$user->role->feeWord . '='));
        $this->fpdi->Text(70, 85, iconv('UTF-8', 'WINDOWS-1250', 'účastnický poplatek '.$this->dbsettings->get('seminar_name')));
        $this->fpdi->Text(70, 92, iconv('UTF-8', 'WINDOWS-1250', "{$user->firstName} {$user->lastName}"));
        $this->fpdi->Text(70, 99, iconv('UTF-8', 'WINDOWS-1250', "{$user->street}, {$user->city}, {$user->postcode}"));


        $this->fpdi->Text(75, 111, iconv('UTF-8', 'WINDOWS-1250', "{$this->writeToday()}"));
        $this->fpdi->Text(35, 111, iconv('UTF-8', 'WINDOWS-1250', "{$this->writeToday()}"));

        $this->fpdi->Text(130, 119, iconv('UTF-8', 'WINDOWS-1250', "{$this->dbsettings->get('accountant')}"));

    }

    protected function configureForIncomeProof()
    {
        $pagecount = $this->fpdi->setSourceFile(WWW_DIR .'/print/pokladni-prijmovy-doklad.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }


    protected function configurForAccountProof()
    {
        $pagecount = $this->fpdi->setSourceFile(WWW_DIR .'/print/potvrzeni-o-prijeti-platby.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;

    }

    protected function writeToday()
    {
        $today = new \DateTime('now');
        return $today->format("d.m. Y");
    }




}
