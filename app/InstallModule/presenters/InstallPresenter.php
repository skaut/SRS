<?php

declare(strict_types=1);

namespace App\InstallModule\Presenters;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\EntityManagerDecorator;
use App\Model\Settings\Settings;
use App\Model\Settings\SettingsException;
use App\Model\Settings\SettingsFacade;
use App\Model\Structure\SubeventRepository;
use App\Model\User\UserRepository;
use App\Services\ApplicationService;
use Contributte\Console\Application;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Nette\Application\AbortException;
use Skautis\Config;
use Skautis\Skautis;
use Skautis\User;
use Skautis\Wsdl\WebServiceFactory;
use Skautis\Wsdl\WsdlException;
use Skautis\Wsdl\WsdlManager;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Obsluhuje instalačního průvodce.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class InstallPresenter extends InstallBasePresenter
{
    /**
     * @var Application
     * @inject
     */
    public $consoleApplication;

    /**
     * @var EntityManagerDecorator
     * @inject
     */
    public $em;

    /**
     * @var SettingsFacade
     * @inject
     */
    public $settingsFacade;

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
     * @throws AbortException
     * @throws \Throwable
     */
    public function renderDefault() : void
    {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }

        try {
            if ($this->settingsFacade->getBoolValue(Settings::ADMIN_CREATED)) {
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
    public function handleImportSchema() : void
    {
        $input  = new ArrayInput([
            'command' => 'migrations:migrate',
            '--no-interaction' => true,
        ]);
        $output = new BufferedOutput();

        $this->consoleApplication->add(new MigrateCommand());
        $this->consoleApplication->setAutoExit(false);
        $result = $this->consoleApplication->run($input, $output);

        if ($result !== 0) {
            $this->flashMessage('install.schema.schema_create_unsuccessful', 'danger');
            return;
        }

        $this->redirect('admin');
    }

    /**
     * Zobrazení stránky pro vytvoření administrátora.
     * @throws AbortException
     * @throws \Throwable
     */
    public function renderAdmin() : void
    {
        try {
            if ($this->settingsFacade->getBoolValue(Settings::ADMIN_CREATED)) {
                $this->flashMessage('install.admin.admin_already_created', 'info');
                $this->redirect('finish');
            }
        } catch (TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (SettingsException $ex) {
            $this->redirect('default');
        }

        if (! $this->user->isLoggedIn()) {
            return;
        }

        $this->em->transactional(function ($em) : void {
            $user = $this->userRepository->findById($this->user->id);
            $this->userRepository->save($user);

            $adminRole        = $this->roleRepository->findBySystemName(Role::ADMIN);
            $implicitSubevent = $this->subeventRepository->findImplicit();

            $this->applicationService->register(
                $user,
                new ArrayCollection([$adminRole]),
                new ArrayCollection([$implicitSubevent]),
                $user,
                true
            );

            $this->settingsFacade->setBoolValue(Settings::ADMIN_CREATED, true);
        });

        $this->user->logout(true);

        $this->redirect('finish');
    }

    /**
     * Otestování připojení ke skautIS, přesměrování na přihlašovací stránku.
     * @throws AbortException
     */
    public function handleCreateAdmin() : void
    {
        $this->redirect(':Auth:login', ['backlink' => ':Install:Install:admin']);
    }

    /**
     * Zobrazení stránky po úspěšné instalaci.
     * @throws AbortException
     * @throws \Throwable
     */
    public function renderFinish() : void
    {
        try {
            if (! $this->settingsFacade->getBoolValue(Settings::ADMIN_CREATED)) {
                $this->redirect('default');
            }
        } catch (TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (SettingsException $ex) {
            $this->redirect('default');
        }
    }

    /**
     * Zobrazení stránky pokud byla instalace dokončena dříve.
     * @throws AbortException
     * @throws \Throwable
     */
    public function renderInstalled() : void
    {
        try {
            if (! $this->settingsFacade->getBoolValue(Settings::ADMIN_CREATED)) {
                $this->redirect('default');
            }
        } catch (TableNotFoundException $ex) {
            $this->redirect('default');
        } catch (SettingsException $ex) {
            $this->redirect('default');
        }
    }

    /**
     * Vyzkouší připojení ke skautIS pomocí anonymní funkce.
     */
    private function checkSkautISConnection() : bool
    {
        try {
            $wsdlManager = new WsdlManager(new WebServiceFactory(), new Config($this->context->parameters['skautIS']['appId'], $this->context->parameters['skautIS']['test']));
            $skautIS     = new Skautis($wsdlManager, new User($wsdlManager));
            $skautIS->org->UnitAllRegistryBasic();
        } catch (WsdlException $ex) {
            return false;
        }
        return true;
    }
}
