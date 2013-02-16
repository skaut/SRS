<?php


namespace InstallModule;
error_reporting(0);

class InstallPresenter extends \SRS\BaseComponentsPresenter
{

	public function renderDefault()
	{
        // pri testovani muze nastat situace, kdy jsme prihlaseni byt v DB nejsme, to by v ostrem provozu nemelo nastat
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }


        if ($this->context->parameters['database']['installed']) {
            $this->flashMessage('Připojení k databázi již bylo nakonfigurováno');
            $this->redirect(':Install:install:schema');
        }

	}

    public function renderSchema() {
        if (!$this->context->parameters['database']['installed']) {
            $this->flashMessage('nejprve nastavte připojení k databázi');
            $this->redirect(':Install:install:default');
        }
        try {
            if ($this->context->database->getRepository('\SRS\model\Settings')->get('schema_imported') == true) {
                $this->flashMessage('Schéma databáze bylo již naimportováno');
                $this->redirect(':Install:install:admin');
            }
        } catch (\Doctrine\DBAL\DBALException $e) {
            //do nothing
        }



    }

    public function handleImportDB() {
        if (!$this->context->parameters['database']['installed']) {
            $this->flashMessage('nejprve nastavte připojení k databázi');
            $this->redirect(':Install:install:default');
        }




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
            //role
            $options = array('command' => 'srs:initial-data:acl');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->run($input, $output);

            //settings
            $options = array('command' => 'srs:initial-data:settings');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->run($input, $output);

            //cms
            $options = array('command' => 'srs:initial-data:cms');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->run($input, $output);

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
        if ($this->context->database->getRepository('\SRS\model\Settings')->get('superadmin_created') == true) {
            $this->flashMessage('Administrátorská role byla již nastavena dříve');
            $this->redirect(':Install:install:finish?before=true');
        }
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
            $this->context->database->getRepository('\SRS\model\Settings')->set('superadmin_created', '1');
            $this->flashMessage('Administrátorská role nastavena');

            $this->redirect(':Install:install:finish');
        }
        $this->template->backlink = $this->backlink();
    }

    public function renderFinish() {
        $this->template->installedEarlier = $this->getParameter('before');
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
