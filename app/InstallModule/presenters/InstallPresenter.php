<?php

/**
 * Homepage presenter.
 */
namespace InstallModule;
error_reporting(0);

class InstallPresenter extends \Nette\Application\UI\Presenter
{

	public function renderDefault()
	{
        $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR.'/config/config.neon'));
        $isDebug = $config['common']['parameters']['debug'];
        $environment = $isDebug == true ? 'development': 'production';
        $DBParams = $config["{$environment} < common"]['parameters']['database'];
        $isConn = $this->IsDBConnection($DBParams['dbname'], $DBParams['host'], $DBParams['user'], $DBParams['password']);


        $this->template->anyVariable = 'any value';
	}


    public function IsDBConnection($dbname, $host, $user, $password) {
        try {
            $dsn = "mysql:host={$host};dbname={$dbname}";
            $dbh = new \PDO($dsn, $user, $password);
        } catch(\PDOException $e) {
            return false;
        }
        return true;
    }

    protected function createComponentDatabaseForm() {
        return new \SRS\Form\Install\DatabaseForm();
    }

}
