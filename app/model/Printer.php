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


    protected function addIncomeProof(\SRS\Model\User $user)
    {
        $this->fpdi->addPage();
        $this->fpdi->useTemplate($this->template, 0, 0);
        $this->fpdi->SetY(49);
        $this->fpdi->SetX(32);
        $this->fpdi->MultiCell(100, 4.5, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('company')));
        $this->fpdi->Text(50, 77, iconv('UTF-8', 'WINDOWS-1250', $this->dbsettings->get('ico')));
        $this->fpdi->Text(50, 150, iconv('UTF-8', 'WINDOWS-1250', $user->role->fee));

        $this->fpdi->Text(50, 125, iconv('UTF-8', 'WINDOWS-1250', $user->firstName . ' ' .  $user->lastName));

    }

    protected function configureForIncomeProof()
    {
        $pagecount = $this->fpdi->setSourceFile(WWW_DIR .'/print/pokladni-prijmovy-doklad.pdf');
        $template = $this->fpdi->importPage(1, '/MediaBox');
        $this->template = $template;
    }



}
