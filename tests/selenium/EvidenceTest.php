<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 7.4.13
 * Time: 22:20
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Test;

class EvidenceTest extends BaseSeleniumTest
{
    /**
     * @group evidence
     */
    public function testBasic()
    {
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Evidence účastníků");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Zobrazit/Skrýt sloupce");
        $this->click("id=frmcolumnForm-city");
        $this->click("id=frmcolumnForm-birthdate");
        $this->click("id=frmcolumnForm-submit");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("id=frmgridForm-evidenceGrid-action-row_1");
        $this->select("id=frm-evidenceGrid-action-action_name", "label=Označit jako přítomné");
        $this->click("id=frmgridForm-evidenceGrid-action-send");
        $this->click("link=Zobrazit detail");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmevidenceDetailForm-paymentMethod", "label=Na účet");
        $this->click("id=frmevidenceDetailForm-submit");
    }

}
