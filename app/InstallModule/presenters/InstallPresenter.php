<?php

namespace App\InstallModule\Presenters;

use App\Commands\InitDataCommand;
use Doctrine\ORM\Tools\Console\Command\SchemaTool\CreateCommand;
use Nette\Application\UI;
use Symfony\Component\Console\Input\ArrayInput;
use Kdyby\Console\StringOutput;

/**
 * Obsluhuje instalacniho pruvodce
 */
class InstallPresenter extends InstallBasePresenter //TODO
{
    /**
     * @var \App\InstallModule\Forms\DatabaseFormFactory
     * @inject
     */
    public $databaseFormFactory;

    /**
     * @var \App\InstallModule\Forms\SkautISFormFactory
     * @inject
     */
    public $skautISFormFactory;

    /**
     * @var \App\ConfigFacade
     * @inject
     */
    public $configFacade;

    /**
     * @var \Kdyby\Console\Application
     * @inject
     */
    public $application;

    public function renderDefault()
    {
        // pri testovani muze nastat situace, kdy jsme prihlaseni byt v DB nejsme, to by v ostrem provozu nemelo nastat
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }

        $this->checkInstallationStatus();

        if ($this->context->parameters['installed']['connection']) {
            $this->flashMessage('Připojení k databázi již bylo nakonfigurováno');
            $this->redirect('schema');
        }
    }

    public function renderSchema()
    {
        $this->checkInstallationStatus();

        if (!$this->context->parameters['installed']['connection']) {
            $this->redirect('connection');
        }

        if ($this->context->parameters['installed']['schema']) {
            $this->flashMessage('Schéma databáze bylo již naimportováno');
            $this->redirect('skautIS');
        }
    }

    public function handleImportSchema() {
        $this->application->add(new CreateCommand());
        $this->application->add(new InitDataCommand());

        $output = new StringOutput;

        $input = new ArrayInput([
            'command' => 'orm:schema-tool:create'
        ]);
        $this->application->run($input, $output);

        if ($output->getOutput() != "") {
            $this->flashMessage("Databazi se nepodarilo vytvorit"); //TODO
            return;
        }

        $input = new ArrayInput([
            'command' => 'app:init-data:load'
        ]);
        $this->application->run($input, $output);

        if ($output->getOutput() != "") {
            $this->flashMessage("Databazi se nepodarilo inicializovat"); //TODO
            return;
        }

        $this->redirect('skautIS');
    }

    public function renderSkautIS()
    {
        $this->checkInstallationStatus();

        if (!$this->context->parameters['installed']['connection']) {
            $this->redirect('connection');
        }

        if (!$this->context->parameters['installed']['schema']) {
            $this->redirect('schema');
        }

        if ($this->context->parameters['installed']['skautis']) {
            $this->flashMessage('Skaut IS byl již nastaven');
            $this->redirect('admin');
        }
    }

    public function renderAdmin()
    {
        $this->checkInstallationStatus();

        if (!$this->context->parameters['installed']['connection']) {
            $this->redirect('connection');
        }

        if (!$this->context->parameters['installed']['schema']) {
            $this->redirect('schema');
        }

        if (!$this->context->parameters['installed']['skautis']) {
            $this->redirect('skautis');
        }

        if ($this->context->parameters['installed']['admin']) {
            $this->flashMessage('Administrátorská role byla již nastavena dříve');
            $this->redirect('finish');
        }

        if ($this->user->isLoggedIn()) {
            $adminRole = $this->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('name' => Role::ADMIN));
            if ($adminRole == null) {
                throw new \Nette\Application\BadRequestException($message = 'Administrátorská role neexistuje!', $code = 500);
            }
            $user = $this->context->database->getRepository('\SRS\Model\User')->find($this->user->id);
            if ($user == null) {
                throw new \Nette\Application\BadRequestException($message = 'Uživatel je sice přihlášen ale v DB neexistuje!', $code = 500);
            }
            $user->removeRole(Role::REGISTERED);
            $user->addRole($adminRole);
            $this->context->database->flush();
            $this->user->logout(true);
            $this->context->database->getRepository('\SRS\model\Settings')->set('superadmin_created', '1');
            $this->flashMessage('Administrátorská role nastavena', 'success');

            $this->redirect(':Install:install:finish');
        }
        $this->template->backlink = $this->backlink();
    }

    public function renderFinish()
    {
        $this->checkInstallationStatus();

    }

    public function renderInstalled()
    {
        $this->checkInstallationError();
    }

    public function handleImportDB()
    {
        $success = true;
        try {
            $options = array('command' => 'orm:schema:create');
            $output = new \Symfony\Component\Console\Output\NullOutput();
            $input = new \Symfony\Component\Console\Input\ArrayInput($options);
            $this->context->console->application->setAutoExit(false);
            $this->context->console->application->run($input, $output);


        } catch (\Doctrine\ORM\Tools\ToolsException $e) {
            $this->flashMessage('Nahrání schéma databáze se nepodařilo', 'error');
            $this->flashMessage('Je pravděpodobné, že Databáze již existuje');
            $this->flashMessage($e->getCode() . ': ' . $e->getMessage());
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
            $this->flashMessage('Nahrání inicializačních dat se nepodařilo', 'error');
            $this->flashMessage($e->getCode() . ': ' . $e->getMessage());
        }


        if ($success == true) {
            $config = \Nette\Utils\Neon::decode(file_get_contents(APP_DIR . '/config/config.neon'));
            $isDebug = $config['common']['parameters']['debug'];
            $environment = $isDebug == true ? 'development' : 'production';
            $config["{$environment} < common"]['parameters']['database']['schema_imported'] = true;
            $configFile = \Nette\Utils\Neon::encode($config, \Nette\Utils\Neon::BLOCK);
            $configUploaded = \file_put_contents(APP_DIR . '/config/config.neon', $configFile);
            $this->flashMessage('Import schématu databáze a inicializačních dat proběhl úspěšně', 'success');
            $this->redirect(':Install:install:skautIS');
        }
        $this->redirect('this');
    }

    protected function createComponentDatabaseForm()
    {
        $form = $this->databaseFormFactory->create();

        $form->onSuccess[] = function (UI\Form $form) {
            $values = $form->getValues();

            if (!$this->checkDBConnection($values['host'], $values['dbname'], $values['user'], $values['password'])) {
                $this->flashMessage('Nepodařilo se připojit k databázi, zadejte správné údaje.', 'alert-danger');
                return;
            }

            $config = $this->configFacade->loadConfig();
            $config['parameters']['installed']['connection'] = true;
            $config['parameters']['database']['host'] = $values['host'];
            $config['parameters']['database']['dbname'] = $values['dbname'];
            $config['parameters']['database']['user'] = $values['user'];
            $config['parameters']['database']['password'] = $values['password'];
            $result = $this->configFacade->saveConfig($config);

            if ($result === false) {
                $this->presenter->flashMessage('Nastavení se nepodařilo uložit. Zkontrolujte práva souboru config.local.neon.', 'alert-danger');
                return;
            }

            $this->redirect('schema');
        };

        return $form;
    }

    protected function createComponentSkautISForm()
    {
        return $this->skautISFormFactory->create();
    }

    private function checkInstallationStatus()
    {
        if ($this->context->parameters['installed']['connection'] &&
            $this->context->parameters['installed']['schema'] &&
            $this->context->parameters['installed']['skautIS'] &&
            $this->context->parameters['installed']['admin']
        ) {
            $this->redirect('installed');
        }

        $this->checkInstallationError();
    }

    private function checkInstallationError() {
        if ((!$this->context->parameters['installed']['connection'] && (
                    $this->context->parameters['installed']['schema'] ||
                    $this->context->parameters['installed']['skautIS'] ||
                    $this->context->parameters['installed']['admin']))
            ||
            (!$this->context->parameters['installed']['schema'] && (
                    $this->context->parameters['installed']['skautIS'] ||
                    $this->context->parameters['installed']['admin']))
            ||
            (!$this->context->parameters['installed']['skautIS'] &&
                $this->context->parameters['installed']['admin'])
        ) {
            $this->redirect('error');
        }
    }

    private function checkDBConnection($host, $dbname, $user, $password) {
        try {
            $dsn = "mysql:host={$host};dbname={$dbname}";
            $dbh = new \PDO($dsn, $user, $password);
        } catch (\PDOException $e) {
            return false;
        }
        return true;
    }
}
