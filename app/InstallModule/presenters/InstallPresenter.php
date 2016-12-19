<?php

namespace App\InstallModule\Presenters;

use App\Commands\FixturesLoadCommand;
use App\Commands\InitDataCommand;
use Kdyby\Doctrine\Console\SchemaCreateCommand;
use Nette\Application\UI;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Kdyby\Console\StringOutput;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use App\Model\ACL\Role;

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
     * @var \App\Services\ConfigFacade
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

    /**
     * @var \App\Model\User\UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var \App\Model\ACL\RoleRepository
     * @inject
     */
    public $roleRepository;

    public function renderDefault()
    {
        // pri testovani muze nastat situace, kdy jsme prihlaseni byt v DB nejsme, to by v ostrem provozu nemelo nastat
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }

        if ($this->checkInstallationStatus())
            $this->redirect('installed');

        if ($this->context->parameters['installed']['connection']) {
            $this->flashMessage($this->translator->translate('install.database.connection_already_configured'), 'alert-info');
            $this->redirect('schema');
        }
    }

    public function renderSchema()
    {
        if ($this->checkInstallationStatus())
            $this->redirect('installed');

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
        if ($this->checkInstallationStatus())
            $this->redirect('installed');

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
        if ($this->checkInstallationStatus())
            $this->redirect('installed');

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
            $this->flashMessage($this->translator->translate('install.admin.admin_already_created'), 'alert-info');
            $this->redirect('finish');
        }

        $this->template->backlink = ':Install:Install:admin';

        if ($this->user->isLoggedIn()) {
            $user = $this->userRepository->findUserById($this->user->id);

            $unregisteredRole = $this->roleRepository->findRoleByUntranslatedName(Role::UNREGISTERED);
            $user->removeRole($unregisteredRole);

            $adminRole = $this->roleRepository->findRoleByUntranslatedName(Role::ADMIN);
            $user->addRole($adminRole);

            $this->em->flush();
            $this->user->logout(true);

            $config = $this->configFacade->loadConfig();
            $config['parameters']['installed']['admin'] = true;
            $result = $this->configFacade->saveConfig($config);

            if ($result === false) {
                $this->presenter->flashMessage($this->translator->translate('install.common.config_save_unsuccessful'), 'alert-danger');
                return;
            }

            $this->redirect('finish');
        }
    }

    public function renderFinish()
    {
        if ($this->checkInstallationError())
            $this->redirect('error');
    }

    public function renderInstalled()
    {
        if ($this->checkInstallationError())
            $this->redirect('error');

        $this->template->migrationAvailable = false; //TODO dostupnost migraci
    }

    public function handleMigrate()
    {
        //TODO spusteni migraci
    }

    public function renderError()
    {
        if (!$this->checkInstallationError())
            $this->redirect('default');
    }

    public function handleReinstall()
    {
        $config = $this->configFacade->loadConfig();
        $config['parameters']['installed']['connection'] = false;
        $config['parameters']['installed']['schema'] = false;
        $config['parameters']['installed']['skautIS'] = false;
        $config['parameters']['installed']['admin'] = false;
        $result = $this->configFacade->saveConfig($config);

        if ($result === false) {
            $this->presenter->flashMessage($this->translator->translate('install.common.config_save_unsuccessful'), 'alert-danger');
            return;
        }

        $this->redirect('default');
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

            if (!$this->checkSkautISConnection($appId, $version)) {
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

    private function checkDBConnection($host, $dbname, $user, $password) {
        try {
            $dsn = "mysql:host={$host};dbname={$dbname}";
            $dbh = new \PDO($dsn, $user, $password);
        } catch (\PDOException $e) {
            return false;
        }
        return true;
    }

    private function checkSkautISConnection($appId, $version) {
        try {
            $skautis = \Skautis\Skautis::getInstance($appId, $version);
            $skautis->org->UnitAllRegistry();
        } catch (\Skautis\Wsdl\WsdlException $ex) {
            return false;
        }
        return true;
    }
}
