<?php
/**
 * User: Michal
 * Date: 22.3.13
 * Time: 13:51
 */

namespace SRS\Test;

class CMSTest extends BaseSeleniumTest
{
    /**
     * @group cms
     */
    public function testContents()
    {
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=CMS");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("xpath=(//a[contains(text(),'Upravit')])[1]");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmpageForm-add_content", "label=Text");
        $this->click("id=frmpageForm-submit_content");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmpageForm-add_content", "label=Obrázek");
        $this->click("css=option[value=\"Image\"]");
        $this->click("id=frmpageForm-submit_content");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmpageForm-add_content", "label=Dokumenty");
        $this->click("id=frmpageForm-submit_content");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmpageForm-add_content", "label=Přihlašovací formulář");
        $this->click("css=option[value=\"AttendeeBox\"]");
        $this->click("id=frmpageForm-submit_content");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmpageForm-add_content", "label=HTML box");
        $this->click("id=frmpageForm-submit_content");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmpageForm-add_content", "label=FAQ");
        $this->click("id=frmpageForm-submit_content");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmpageForm-add_content", "label=Aktuality");
        $this->click("id=frmpageForm-submit_content");
        $this->waitForPageToLoad(self::WAIT);
        $this->select("id=frmpageForm-add_content", "label=Výběr programů");
        $this->click("id=frmpageForm-submit_content");
        $this->waitForPageToLoad(self::WAIT);
    }

    /**
     * @group cms
     */
    public function testAddItems()
    {
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=CMS");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Přidat stránku");
        $this->type("id=frmnewPageForm-name", "test");
        $this->type("id=frmnewPageForm-slug", \Nette\Utils\Strings::random());
        $this->click("id=frmnewPageForm-submit");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=FAQ");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Přidat Otázku");
        $this->waitForPageToLoad(self::WAIT);
        $this->type("id=frmfaqForm-question", "test");
        $this->click("id=frmfaqForm-submit");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Štítky Dokumentů");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Přidat Štítek");
        $this->type("id=frmtagForm-name", "test");
        $this->click("id=frmtagForm-submit");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Dokumenty");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Přidat Dokument");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("//form[@id='frm-documentForm']/div/table/tbody/tr[2]/td[2]/div/div[2]/ul/li[3]/a/span");
        $this->type("id=frmdocumentForm-name", "test");
        $this->click("id=frmdocumentForm-submit");
        $this->waitForPageToLoad(self::WAIT);
        $this->assertTextPresent('vyplnit');
    }



}
