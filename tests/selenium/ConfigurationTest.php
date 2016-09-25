<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 7.4.13
 * Time: 21:46
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Test;

class ConfigurationTest extends BaseSeleniumTest
{
    /**
     * @group configuration
     */
    public function testConfiguration()
  {
      $this->login();
      $this->click("link=Administrace");
      $this->waitForPageToLoad(self::WAIT);
      $this->click("link=Konfigurace");
      $this->waitForPageToLoad(self::WAIT);
      $this->type("id=frmsettingsForm-seminar_name", "Jmeno");
      $this->click("css=fieldset");
      $this->type("id=frmsettingsForm-user_custom_boolean_0", "barva");
      $this->click("id=frmsettingsForm-submit");
      $this->waitForPageToLoad(self::WAIT);
  }

}
