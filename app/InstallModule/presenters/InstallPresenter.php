<?php

namespace App\InstallModule\Presenters;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsRepository;
use App\Model\Structure\SubeventRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Kdyby\Console\Application;
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
     * @var Application
     * @inject
     */
    public $application;

    /**
     * @var SettingsRepository
     * @inject
     */
    public $settingsRepository;

    /**
     * @var RoleRepository
     * @inject
     */
    public $roleRepository;

    /**
     * @var UserRepository
     * @inject
     */
    public $userRepository;

    /**
     * @var SubeventRepository
     * @inject
     */
    public $subeventRepository;

    /**
     * @var ApplicationService
     * @inject
     */
    public $applicationService;


    /**
     * Zobrazení první stránky průvodce.
     * @throws \Nette\Application\AbortException
     */
    public function renderDefault()
    {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(TRUE);
        }

        try {
            if ($this->settingsRepository->getBoolValue(Settings::ADMIN_CREATED)) {
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
     * @throws \Exception
     */
    public function handleImportSchema()
    {
        $this->application->add(new MigrateCommand());
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
     * @throws \Nette\Application\AbortException
     * @throws \Throwable
     */
    public function renderAdmin()
    {
        try {
            if ($this->settingsRepository->getBoolValue(Settings::ADMIN_CREATED)) {
                $this->flashMessage('install.admin.admin_already_created', 'info');
                $this->redirect('finish');
            }
        } catch (TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (SettingsException $ex) {
            $this->redirect('default');
        }

        if ($this->user->isLoggedIn()) {
            $this->userRepository->getEntityManager()->transactional(function ($em) {
                $user = $this->userRepository->findById($this->user->id);
                $this->userRepository->save($user);

                $adminRole = $this->roleRepository->findBySystemName(Role::ADMIN);
                $implicitSubevent = $this->subeventRepository->findImplicit();

                $this->applicationService->register($user, new ArrayCollection([$adminRole]),
                    new ArrayCollection([$implicitSubevent]), $user, TRUE);

                $this->settingsRepository->setValue(Settings::ADMIN_CREATED, TRUE);
            });

            $this->user->logout(TRUE);

            $this->redirect('finish');
        }
    }

    /**
     * Otestování připojení ke skautIS, přesměrování na přihlašovací stránku.
     * @throws \Nette\Application\AbortException
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
     * @throws \Nette\Application\AbortException
     */
    public function renderFinish()
    {
        try {
            if (!$this->settingsRepository->getBoolValue(Settings::ADMIN_CREATED))
                $this->redirect('default');
        } catch (TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (SettingsException $ex) {
            $this->redirect('default');
        }
    }

    /**
     * Zobrazení stránky pokud byla instalace dokončena dříve.
     * @throws \Nette\Application\AbortException
     */
    public function renderInstalled()
    {
        try {
            if (!$this->settingsRepository->getBoolValue(Settings::ADMIN_CREATED))
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
            $skautIS->org->UnitAllRegistry();
        } catch (WsdlException $ex) {
            return FALSE;
        }
        return TRUE;
    }
}
