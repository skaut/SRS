<?php

namespace App\InstallModule\Presenters;

use Nette\Application\UI;
use App\Install\Forms\SkautISForm;
use App\InstallModule\Forms\DatabaseFormFactory;
use App\Model\Settings\SettingsRepository;


class InstallPresenter extends InstallBasePresenter
{
    /**
     * @var SettingsRepository
     * @Inject
     */
    private $settingsRepository;

    public function renderDefault()
    {
        // pri testovani muze nastat situace, kdy jsme prihlaseni byt v DB nejsme, to by v ostrem provozu nemelo nastat
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }

        if ($this->context->parameters['installed']['connection']) {
            $this->flashMessage('Připojení k databázi již bylo nakonfigurováno');
            $this->redirect(':Install:Install:schema');
        }
    }

    public function renderSchema()
    {
        if (!$this->context->parameters['installed']['connection']) {
            $this->flashMessage('nejprve nastavte připojení k databázi');
            $this->redirect(':Install:Install:default');
        }

        if ($this->context->parameters['installed']['schema']) {
            $this->flashMessage('Schéma databáze bylo již naimportováno');
            $this->redirect(':Install:Install:skautIS');
        }
    }

    public function renderSkautIS()
    {
        if (!$this->context->parameters['installed']['connection']) {
            $this->redirect(':Install:Install:default');
        }

        if (!$this->context->parameters['installed']['schema']) {
            $this->redirect(':Install:Install:schema');
        }

        if ($this->context->parameters['installed']['skautIS']) {
            $this->flashMessage('Skaut IS byl již nastaven');
            $this->redirect(':Install:Install:admin');
        }
    }

    public function renderAdmin()
    {
        if (!$this->context->parameters['installed']['connection']) {
            $this->redirect(':Install:Install:default');
        }

        if (!$this->context->parameters['installed']['schema']) {
            $this->redirect(':Install:Install:schema');
        }

        if (!$this->context->parameters['installed']['skautIS']) {
            $this->redirect(':Install:Install:skautIS');
        }

        if ($this->settingsRepository->get('superadmin_created')) {
            $this->flashMessage('Administrátorská role byla již nastavena dříve');
            $this->redirect(':Install:Install:finish?before=true');
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

            $this->redirect(':Install:Install:finish');
        }
        $this->template->backlink = $this->backlink();
    }

    public function renderFinish()
    {
        if (!$this->context->parameters['installed']['connection']) {
            $this->redirect(':Install:Install:default');
        }

        if (!$this->context->parameters['installed']['schema']) {
            $this->redirect(':Install:Install:schema');
        }

        if (!$this->context->parameters['installed']['skautIS']) {
            $this->redirect(':Install:Install:skautIS');
        }

        if (!$this->settingsRepository->get('superadmin_created')) {
            $this->redirect(':Install:Install:admin');
        }

        $this->template->installedEarlier = $this->getParameter('before');
    }

    public function createComponentDatabaseForm() {
        $form = (new DatabaseFormFactory())->create();
        $form->onSuccess[] = [$this, 'databaseFormSubmitted'];
        return $form;
    }

    public function createComponentSkautISForm() {
        $form = (new SkautISForm())->create();
        $form->onSuccess[] = [$this, 'skautISFormSubmitted'];
        return $form;
    }

    public function databaseFormSubmitted(UI\Form $form, $values) {
        if (!$this->isDBConnection($values['dbname'], $values['host'], $values['user'], $values['password'])) {
            $this->flashMessage('Nepodařilo se připojit k databázi. Zadejte správné údaje', 'error');
        }

        else {
            $this->context->parameters['installed']['database'] = true;
            $this->context->parameters['installed']['schema'] = false;

            $this->context->parameters['database']['host'] = $values['host'];
            $this->context->parameters['database']['dbname'] = $values['dbname'];
            $this->context->parameters['database']['user'] = $values['user'];
            $this->context->parameters['database']['password'] = $values['password'];

            $this->presenter->flashMessage('Spojení s databází úspěšně navázáno', 'success');
            $this->presenter->redirect(':Install:Install:schema');
        }
    }

    public function skautISFormSubmitted(UI\Form $form, $values) {
        $this->context->parameters['skautIS']['appId'] = $values['skautis_app_id'];

        try {
            $skautIS = $this->getService('\Skautis\Skautis');
            $this->skautIS->getWebService("OrganizationUnit")->call("UnitAllRegistryBasic");
            $this->presenter->flashMessage('Oveření skautIS App ID proběhlo úspěšně.', 'success');
            $this->presenter->redirect(':Install:Install:admin');
        } catch (\SoapFault $e) {
            $this->presenter->flashMessage("Nepodařilo se ověřit skautIS App ID. Ujistěte se, že zadáváte správné údaje.", 'error');
        }
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
            $this->redirect(':Install:Install:skautIS');
        }
        $this->redirect('this');
    }

    private function isDBConnection($dbname, $host, $user, $password)
    {
        try {
            $dsn = "mysql:host={$host};dbname={$dbname}";
            $dbh = new \PDO($dsn, $user, $password);
        } catch (\PDOException $e) {
            return false;
        }
        return true;
    }
}