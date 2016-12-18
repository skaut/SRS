<?php

namespace App\InstallModule\Presenters;

use App\Commands\FixturesLoadCommand;
use App\Commands\InitDataCommand;
use InstallModule\presenters\SkautISAccessor;
use Kdyby\Doctrine\Console\SchemaCreateCommand;
use Nette\Application\UI;
use Skautis\Config;
use Skautis\Skautis;
use Skautis\User;
use Skautis\Wsdl\WebServiceFactory;
use Skautis\Wsdl\WsdlManager;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Kdyby\Console\StringOutput;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;

/**
 * Obsluhuje instalacniho pruvodce
 */
class InstallPresenter extends InstallBasePresenter
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

    /**
     * @var \Kdyby\Translation\Translator
     * @inject
     */
    public $translator;

    /**
     * @var \Kdyby\Doctrine\EntityManager
     * @inject
     */
    public $em;

    public function renderDefault()
    {
        // pri testovani muze nastat situace, kdy jsme prihlaseni byt v DB nejsme, to by v ostrem provozu nemelo nastat
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }

        $this->checkInstallationStatus();

        if ($this->context->parameters['installed']['connection']) {
            $this->flashMessage($this->translator->translate('install.database.connection_already_configured'), 'alert-info');
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
            $this->flashMessage($this->translator->translate('install.schema.schema_already_created'), 'alert-info');
            $this->redirect('skautIs');
        }
    }

    public function handleImportSchema() {
        $helperSet = new HelperSet(['em' => new EntityManagerHelper($this->em)]);
        $this->application->setHelperSet($helperSet);

        $this->application->add(new SchemaCreateCommand());
        $this->application->add(new FixturesLoadCommand());

        $output = new StringOutput();

        $input = new ArrayInput([
            'command' => 'orm:schema-tool:create'
        ]);
        $result = $this->application->run($input, $output);

        if ($result != 0) {
            $this->flashMessage($this->translator->translate('install.schema.schema_create_unsuccessful'), 'alert-danger');
            return;
        }

        $input = new ArrayInput([
            'command' => 'app:fixtures:load'
        ]);
        $result = $this->application->run($input, $output);

        if ($result != 0) {
            $this->flashMessage($this->translator->translate('install.schema.data_import_unsuccessful'), 'alert-danger');
            return;
        }

        $config = $this->configFacade->loadConfig();
        $config['parameters']['installed']['schema'] = true;
        $result = $this->configFacade->saveConfig($config);

        if ($result === false) {
            $this->presenter->flashMessage($this->translator->translate('install.common.config_save_unsuccessful'), 'alert-danger');
            return;
        }

        $this->redirect('skautIs');
    }

    public function renderSkautIs()
    {
        $this->checkInstallationStatus();

        if (!$this->context->parameters['installed']['connection']) {
            $this->redirect('connection');
        }

        if (!$this->context->parameters['installed']['schema']) {
            $this->redirect('schema');
        }

        if ($this->context->parameters['installed']['skautIS']) {
            $this->flashMessage($this->translator->translate('install.skautis.skautis_already_configured'), 'alert-info');
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

        if (!$this->context->parameters['installed']['skautIS']) {
            $this->redirect('skautIs');
        }

        if ($this->context->parameters['installed']['admin']) {
            $this->flashMessage('Administrátorská role byla již nastavena dříve');
            $this->redirect('finish');
        }

//        if ($this->user->isLoggedIn()) {
//            $adminRole = $this->context->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('name' => Role::ADMIN));
//            if ($adminRole == null) {
//                throw new \Nette\Application\BadRequestException($message = 'Administrátorská role neexistuje!', $code = 500);
//            }
//            $user = $this->context->database->getRepository('\SRS\Model\User')->find($this->user->id);
//            if ($user == null) {
//                throw new \Nette\Application\BadRequestException($message = 'Uživatel je sice přihlášen ale v DB neexistuje!', $code = 500);
//            }
//            $user->removeRole(Role::REGISTERED);
//            $user->addRole($adminRole);
//            $this->context->database->flush();
//            $this->user->logout(true);
//            $this->context->database->getRepository('\SRS\model\Settings')->set('superadmin_created', '1');
//            $this->flashMessage('Administrátorská role nastavena', 'success');
//
//            $this->redirect(':Install:install:finish');
//        }
//        $this->template->backlink = $this->backlink();
    }

    public function renderFinish()
    {
        $this->checkInstallationStatus();
    }

    public function renderInstalled()
    {
        $this->checkInstallationError();
    }

    protected function createComponentDatabaseForm()
    {
        $form = $this->databaseFormFactory->create();

        $form->onSuccess[] = function (UI\Form $form) {
            $values = $form->getValues();

            if (!$this->checkDBConnection($values['host'], $values['dbname'], $values['user'], $values['password'])) {
                $this->flashMessage($this->translator->translate('install.database.database_connection_unsuccessful'), 'alert-danger');
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
                $this->presenter->flashMessage($this->translator->translate('install.common.config_save_unsuccessful'), 'alert-danger');
                return;
            }

            $this->redirect('schema');
        };

        return $form;
    }

    protected function createComponentSkautISForm()
    {
        $form = $this->skautISFormFactory->create();

        $form->onSuccess[] = function (UI\Form $form) {
            $values = $form->getValues();

            $appId = $values['skautis_app_id'];
            $version = filter_var($values['skautis_version'], FILTER_VALIDATE_BOOLEAN);

            try {
                $wsdlManager = new WsdlManager(new WebServiceFactory(), new Config($appId, $version));
                $skautis = new Skautis($wsdlManager, new User($wsdlManager));
                $skautis->getWebService('OrganizationUnit')->call('UnitAllRegistry');
            } catch (\Skautis\Wsdl\WsdlException $ex) {
                $this->flashMessage($this->translator->translate('install.skautis.skautis_access_denied'), 'alert-danger');
                return;
            }

            $config = $this->configFacade->loadConfig();
            $config['parameters']['installed']['skautIS'] = true;
            $config['parameters']['skautIS']['appId'] = $appId;
            $config['parameters']['skautIS']['test'] = $version;
            $result = $this->configFacade->saveConfig($config);

            if ($result === false) {
                $this->presenter->flashMessage($this->translator->translate('install.common.config_save_unsuccessful'), 'alert-danger');
                return;
            }

            $this->redirect('admin');
        };

        return $form;
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
