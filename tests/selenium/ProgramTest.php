<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 7.4.13
 * Time: 21:46
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Test;

class ProgramTest extends BaseSeleniumTest
{
    /**
     * @group program
     */
    public function testBlocks()
  {
      $this->login();
      $this->click("link=Administrace");
      $this->waitForPageToLoad(self::WAIT);
      $this->click("link=Program semináře");
      $this->waitForPageToLoad(self::WAIT);
      $this->click("link=Vytvořit programový Blok");
      $this->waitForPageToLoad(self::WAIT);
      $this->type("id=frmblockForm-name", "test");
      $this->type("id=frmblockForm-capacity", "10");
      $this->type("id=frmblockForm-tools", "test");
      $this->type("id=frmblockForm-location", "test");
      $this->type("id=frmblockForm-perex", "test");
      $this->click("id=frmblockForm-submit");
      $this->waitForPageToLoad(self::WAIT);
      $this->click("link=Upravit");
      $this->waitForPageToLoad(self::WAIT);
      $this->click("id=frmblockForm-submit");
      $this->waitForPageToLoad(self::WAIT);
      $this->click("link=Smazat");
      $this->click("xpath=(//a[contains(text(),'Smazat')])[2]");
      $this->waitForPageToLoad(self::WAIT);
  }


    /**
     * @group program
     */
    public function testHarmonogramSmoke()
    {
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Program semináře");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Tvorba harmonogramu");
        $this->waitForPageToLoad(self::WAIT);
    }
}
