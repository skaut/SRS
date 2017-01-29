<?php

namespace App\InstallModule\Presenters;

use App\Commands\FixturesLoadCommand;
use App\Commands\InitDataCommand;
use Kdyby\Doctrine\Console\SchemaCreateCommand;
use Nette\Application\UI;
use Nette\Forms\ControlGroup;
use Skautis\Config;
use Skautis\Skautis;
use Skautis\User;
use Skautis\Wsdl\WebServiceFactory;
use Skautis\Wsdl\WsdlManager;
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
     * @var \Kdyby\Console\Application
     * @inject
     */
    public $application;

    /**
     * @var \Skautis\Skautis
     * @inject
     */
    public $skautIS;

    /**
     * @var \Kdyby\Doctrine\EntityManager
     * @inject
     */
    public $em;

    /**
     * @var \App\Model\Settings\SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @var \App\Model\ACL\RoleRepository
     * @inject
     */
    public $roleRepository;

    /**
     * @var \App\Model\User\UserRepository
     * @inject
     */
    public $userRepository;

    public function renderDefault()
    {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }

        try {
            if (filter_var($this->settingsRepository->getValue('admin_created'), FILTER_VALIDATE_BOOLEAN)) {
                $this->redirect('installed');
            }
            $this->flashMessage('install.schema.schema_already_created', 'info');
            $this->redirect('admin');
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex) {
        } catch (\App\Model\Settings\SettingsException $ex) {
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
            $this->flashMessage('install.schema.schema_create_unsuccessful', 'danger');
            return;
        }

        $output = new StringOutput();
        $input = new ArrayInput([
            'command' => 'app:fixtures:load'
        ]);
        $result = $this->application->run($input, $output);

        if ($result != 0) {
            $this->flashMessage('install.schema.data_import_unsuccessful', 'danger');
            return;
        }

        $this->redirect('admin');
    }

    public function renderAdmin()
    {
        try {
            if (filter_var($this->settingsRepository->getValue('admin_created'), FILTER_VALIDATE_BOOLEAN)) {
                $this->flashMessage('install.admin.admin_already_created', 'info');
                $this->redirect('finish');
            }
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (\App\Model\Settings\SettingsException $ex) {
            $this->redirect('default');
        }

        if ($this->user->isLoggedIn()) {
            $user = $this->userRepository->findUserById($this->user->id);

            $unregisteredRole = $this->roleRepository->findRoleByUntranslatedName(Role::UNREGISTERED);
            $user->removeRole($unregisteredRole);

            $adminRole = $this->roleRepository->findRoleByUntranslatedName(Role::ADMIN);
            $user->addRole($adminRole);

            $this->settingsRepository->setValue('admin_created', true);

            $this->em->flush();
            $this->user->logout(true);

            $this->redirect('finish');
        }
    }

    public function handleCreateAdmin()
    {
        if (!$this->checkSkautISConnection()) {
            $this->flashMessage('install.admin.skautis_access_denied', 'danger');
            return;
        }
        $this->redirect(':Auth:login', ['backlink' => ':Install:Install:admin']);
    }

    public function renderFinish()
    {
        try {
            if (!filter_var($this->settingsRepository->getValue('admin_created'), FILTER_VALIDATE_BOOLEAN))
                $this->redirect('default');
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (\App\Model\Settings\SettingsException $ex) {
            $this->redirect('default');
        }
    }

    public function renderInstalled()
    {
        try {
            if (!filter_var($this->settingsRepository->getValue('admin_created'), FILTER_VALIDATE_BOOLEAN))
                $this->redirect('default');
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (\App\Model\Settings\SettingsException $ex) {
            $this->redirect('default');
        }

        $this->template->migrationAvailable = false; //TODO dostupnost migraci
    }

    public function handleMigrate()
    {
        //TODO spusteni migraci
    }

    private function checkSkautISConnection() {
        try {
            $wsdlManager = new WsdlManager(new WebServiceFactory(), new Config($this->context->parameters['skautIS']['appId'], $this->context->parameters['skautIS']['test']));
            $skautIS = new Skautis($wsdlManager, new User($wsdlManager));
            $skautIS->org->UnitAllRegistry();
        } catch (\Skautis\Wsdl\WsdlException $ex) {
            return false;
        }
        return true;
    }
}
