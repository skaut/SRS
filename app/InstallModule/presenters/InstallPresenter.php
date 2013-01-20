<?php


namespace InstallModule;
error_reporting(0);

class InstallPresenter extends \Nette\Application\UI\Presenter
{

	public function renderDefault()
	{
        // pri testovani muze nastat situace, kdy jsme prihlaseni byt v DB nejsme, to by v ostrem provozu nemelo nastat
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }

        $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR.'/config/config.neon'));
        $isDebug = $config['common']['parameters']['debug'];
        $environment = $isDebug == true ? 'development': 'production';
        $DBParams = $config["{$environment} < common"]['parameters']['database'];
        $isConn = $this->IsDBConnection($DBParams['dbname'], $DBParams['host'], $DBParams['user'], $DBParams['password']);


        $this->template->anyVariable = 'any value';
	}

    public function renderSchema() {


//        $arguments = array(
//        );
//
//       $input = new \Symfony\Component\Console\Input\ArrayInput($arguments);
//       $output = new \Symfony\Component\Console\Output\NullOutput();
//       $command = $this->context->RoleInitialDataCommand->run($input, $output);

    }

    public function handleImportDB() {
        $success = true;
        try {
            $options = array('command' => 'orm:schema:create');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->setAutoExit(false);
            $this->context->console->application->run($input, $output);




        } catch (\Doctrine\ORM\Tools\ToolsException $e) {
            $this->flashMessage('Nahrání schéma databáze se nepodařilo');
            $this->flashMessage('Je pravděpodobné, že Databáze již existuje');
            $this->flashMessage($e->getCode(). ': ' . $e->getMessage());
            $success = false;
        }

        try {
            $options = array('command' => 'srs:initial-data:acl');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->doRun($input, $output);
        } catch (\Doctrine\DBAL\DBALException $e) {
            $success = false;
            $this->template->error = $e->getCode();
            $this->flashMessage('Nahrání inicializačních dat se nepodařilo');
            $this->flashMessage($e->getCode(). ': ' . $e->getMessage());
        }



        if ($success == true) {
            $this->flashMessage('Import schématu databáze a inicializačních dat proběhl úspěšně');
            $this->redirect(':Install:install:admin');
        }
        $this->redirect('this');
    }

    public function renderAdmin() {
        if ($this->user->isLoggedIn()) {
            $adminRole = $this->context->database->getRepository('\SRS\Model\Acl\Role')->findByName('Administrátor');
            if ($adminRole == null) {
                throw new \Nette\Application\BadRequestException($message = 'Administrátorská role neexistuje!', $code = 500);
            }
            $adminRole = $adminRole[0];
            $user = $this->context->database->getRepository('\SRS\Model\User')->find($this->user->id);
            if ($user == null) {
                throw new \Nette\Application\BadRequestException($message = 'Uživatel je sice přihlášen ale v DB neexistuje!', $code = 500);
            }
            $user->role = $adminRole;
            $this->context->database->flush();
            $this->user->logout(true);
            $this->flashMessage('Administrátorská role nastavena');

            $this->redirect(':Install:install:finish');
        }
        $this->template->backlink = $this->backlink();
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
