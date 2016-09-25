<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 2.3.13
 * Time: 22:01
 * Author: Michal Májský
 */

namespace SRS\Test;
abstract class BaseSeleniumTest extends \PHPUnit_Extensions_SeleniumTestCase
{
    protected $config;

    const WAIT = '30000';

    protected function setUp()
    {
        $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR . '/config/config.neon'));
        $this->config = $config['common']['parameters']['tests'];
        $this->setBrowser("firefox");
        $this->setBrowserUrl("http://".$this->config['url']);
    }

    protected function login($role = 'administrator')
    {
        $this->open("/");
        $this->click("link=Přihlásit");
        $this->waitForPageToLoad(self::WAIT);
        $this->type("id=ctl00_txtUserName", $this->config['roles'][$role]['user']);
        $this->type("id=ctl00_txtPassword", $this->config['roles'][$role]['password']);
        $this->click("id=btnLogin");
        $this->waitForLocation('*'.$this->config['url'].'*');

    }
}
