<?php

declare(strict_types=1);

use Codeception\Actor;


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    private const LOGIN = 'srs-test';
    private const PASSWORD = 'test-srs1';

    /**
     * @throws Exception
     */
    public function login() : void
    {
        $I = $this;
        if ($I->loadSessionSnapshot('login')) {
            return;
        }
        $I->amOnPage('/');
        $I->click('Přihlásit');
        $I->see('skautIS přihlášení do aplikace');
        $I->fillField('(//input)[9]', self::LOGIN);
        $I->fillField('(//input)[10]', self::PASSWORD);
        $I->click('//button');
        $I->waitForText('Uživatel: ' . self::LOGIN . ' v roli');
        $I->saveSessionSnapshot('login');
    }
}
