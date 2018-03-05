<?php

namespace App\InstallModule\Presenters;

use App\Commands\FixturesLoadCommand;
use App\Model\ACL\Role;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Kdyby\Console\StringOutput;
use Skautis\Config;
use Skautis\Skautis;
use Skautis\User;
use Skautis\Wsdl\WebServiceFactory;
use Skautis\Wsdl\WsdlException;
use Skautis\Wsdl\WsdlManager;
use Symfony\Component\Console\Input\ArrayInput;


/**
 * Obsluhuje instalačního průvodce.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class InstallPresenter extends InstallBasePresenter
{
    /**
     * @var \Kdyby\Console\Application
     * @inject
     */
    public $application;

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


    /**
     * Zobrazení první stránky průvodce.
     */
    public function renderDefault()
    {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(TRUE);
        }

        try {
            if (filter_var($this->settingsRepository->getValue(Settings::ADMIN_CREATED), FILTER_VALIDATE_BOOLEAN)) {
                $this->redirect('installed');
            }
            $this->flashMessage('install.schema.schema_already_created', 'info');
            $this->redirect('admin');
        } catch (TableNotFoundException $ex) {
        } catch (SettingsException $ex) {
        }
    }

    /**
     * Vytvoření schéma databáze a počátečních dat.
     */
    public function handleImportSchema()
    {
        $output = new StringOutput();
        $input = new ArrayInput([
            'command' => 'migrations:migrate',
            '--no-interaction' => TRUE
        ]);
        $result = $this->application->run($input, $output);

        if ($result != 0) {
            $this->flashMessage('install.schema.schema_create_unsuccessful', 'danger');
            return;
        }

        $this->redirect('admin');
    }

    /**
     * Zobrazení stránky pro vytvoření administrátora.
     */
    public function renderAdmin()
    {
        try {
            if (filter_var($this->settingsRepository->getValue(Settings::ADMIN_CREATED), FILTER_VALIDATE_BOOLEAN)) {
                $this->flashMessage('install.admin.admin_already_created', 'info');
                $this->redirect('finish');
            }
        } catch (TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (SettingsException $ex) {
            $this->redirect('default');
        }

        if ($this->user->isLoggedIn()) {
            $user = $this->userRepository->findById($this->user->id);

            $nonregisteredRole = $this->roleRepository->findBySystemName(Role::NONREGISTERED);
            $user->removeRole($nonregisteredRole);

            $adminRole = $this->roleRepository->findBySystemName(Role::ADMIN);
            $user->addRole($adminRole);

            $this->settingsRepository->setValue(Settings::ADMIN_CREATED, TRUE);

            $this->userRepository->save($user);

            $this->user->logout(TRUE);

            $this->redirect('finish');
        }
    }

    /**
     * Otestování připojení ke skautIS, přesměrování na přihlašovací stránku.
     */
    public function handleCreateAdmin()
    {
        if (!$this->checkSkautISConnection()) {
            $this->flashMessage('install.admin.skautis_access_denied', 'danger');
            return;
        }
        $this->redirect(':Auth:login', ['backlink' => ':Install:Install:admin']);
    }

    /**
     * Zobrazení stránky po úspěšné instalaci.
     */
    public function renderFinish()
    {
        try {
            if (!filter_var($this->settingsRepository->getValue(Settings::ADMIN_CREATED), FILTER_VALIDATE_BOOLEAN))
                $this->redirect('default');
        } catch (TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (SettingsException $ex) {
            $this->redirect('default');
        }
    }

    /**
     * Zobrazení stránky pokud byla instalace dokončena dříve.
     */
    public function renderInstalled()
    {
        try {
            if (!filter_var($this->settingsRepository->getValue(Settings::ADMIN_CREATED), FILTER_VALIDATE_BOOLEAN))
                $this->redirect('default');
        } catch (TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (SettingsException $ex) {
            $this->redirect('default');
        }
    }

    /**
     * Vyzkouší připojení ke skautIS pomocí anonymní funkce.
     * @return bool
     */
    private function checkSkautISConnection()
    {
        try {
            $wsdlManager = new WsdlManager(new WebServiceFactory(), new Config($this->context->parameters['skautIS']['appId'], $this->context->parameters['skautIS']['test']));
            $skautIS = new Skautis($wsdlManager, new User($wsdlManager));
            $skautIS->org->UnitAllRegistryBasic();
        } catch (WsdlException $ex) {
            return FALSE;
        }
        return TRUE;
    }
}
