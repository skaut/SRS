<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 4.3.13
 * Time: 22:27
 * Author: Michal Májský
 */
namespace SRS\Test;
require_once 'BaseSeleniumTest.php';
class AclTest extends BaseSeleniumTest
{

    protected $moduleName = 'Práva a role';

    /**
     * @group acl
     */
    public function testaddRole()
    {
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Práva a Role");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Role");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Přidat roli");
        $this->type("id=frmnewRoleForm-name", \Nette\Utils\Strings::random());
        $this->click("id=frmnewRoleForm-submit");
        $this->waitForPageToLoad(self::WAIT);
        $this->addSelection("id=frmroleForm-permissions", "label=Vybírat si programy | Program");
        $this->addSelection("id=frmroleForm-permissions", "label=Přístup | Administrace");
        $this->click("id=frmroleForm-pays");
        $this->click("id=frmroleForm-approvedAfterRegistration");
        $this->click("id=frmroleForm-submit");
        $this->waitForPageToLoad(self::WAIT);
        $this->assertTextPresent($this->moduleName);
    }

    /**
     * @group acl
     */
    public function testeditRole()
    {
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Práva a Role");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Role");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("xpath=(//a[contains(text(),'Upravit')])[2]");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("id=frmroleForm-registerable");
        $this->click("id=frmroleForm-submit");
        $this->waitForPageToLoad(self::WAIT);
    }

}
