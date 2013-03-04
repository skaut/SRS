<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 4.3.13
 * Time: 22:27
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Test;
require_once 'BaseSeleniumTest.php';
class AclTest extends BaseSeleniumTest
{

    /**
     * @group acl
     */
    public function testaddRole()
    {
        //$this->open("/");
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Práva a Role");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Role");
        $this->waitForPageToLoad(self::WAIT);
        $this->click("link=Přidat roli");
        //$this->waitForPageToLoad("30000");
        $this->type("id=frmnewRoleForm-name", \Nette\Utils\Strings::random());
        $this->click("id=frmnewRoleForm-submit");
        $this->waitForPageToLoad(self::WAIT);
        $this->addSelection("id=frmroleForm-permissions", "label=Vybírat si programy | Program");
        $this->addSelection("id=frmroleForm-permissions", "label=Přístup | Administrace");
        $this->click("id=frmroleForm-pays");
        $this->click("id=frmroleForm-approvedAfterRegistration");
        $this->click("id=frmroleForm-submit");
    }

    /**
     * @group acl
     */
    public function testeditRole()
    {
        $this->login();
        $this->click("link=Administrace");
        $this->waitForPageToLoad("30000");
        $this->click("link=Práva a Role");
        $this->waitForPageToLoad("30000");
        $this->click("link=Role");
        $this->waitForPageToLoad("30000");
        $this->click("xpath=(//a[contains(text(),'Upravit')])[2]");
        $this->waitForPageToLoad("30000");
        $this->click("id=frmroleForm-registerable");
        $this->click("id=frmroleForm-submit");
        $this->waitForPageToLoad("30000");
    }

}
