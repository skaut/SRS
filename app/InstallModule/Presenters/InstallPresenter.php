<?php

declare(strict_types=1);

namespace App\InstallModule\Presenters;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Settings\Commands\SetSettingBoolValue;
use App\Model\Settings\Exceptions\SettingsException;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Services\ApplicationService;
use Contributte\Console\Application;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nette\Application\AbortException;
use Skautis\Skautis;
use Skautis\Wsdl\WsdlException;
use Symfony\Component\Console\Input\ArrayInput;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Obsluhuje instalačního průvodce.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class InstallPresenter extends InstallBasePresenter
{
    /** @inject */
    public Application $consoleApplication;

    /** @inject */
    public EntityManagerInterface $em;

    /** @inject */
    public RoleRepository $roleRepository;

    /** @inject */
    public UserRepository $userRepository;

    /** @inject */
    public SubeventRepository $subeventRepository;

    /** @inject */
    public ApplicationService $applicationService;

    /** @inject */
    public Skautis $skautIs;

    /**
     * Zobrazení první stránky průvodce.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function renderDefault(): void
    {
        if ($this->user->isLoggedIn()) {
            $this->user->logout(true);
        }

        try {
            if ($this->queryBus->handle(new SettingBoolValueQuery(Settings::ADMIN_CREATED))) {
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
     *
     * @throws Exception
     */
    public function handleImportSchema(): void
    {
        $this->consoleApplication->add(new MigrateCommand());
        $this->consoleApplication->setAutoExit(false);

        $input = new ArrayInput([
            'command' => 'migrations:migrate',
            '--no-interaction' => true,
        ]);

        $result = $this->consoleApplication->run($input);

        if ($result === 0) {
            $this->redirect('admin');
        }

        $this->flashMessage('install.schema.schema_create_unsuccessful', 'danger');
    }

    /**
     * Zobrazení stránky pro vytvoření administrátora.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function renderAdmin(): void
    {
        try {
            if ($this->queryBus->handle(new SettingBoolValueQuery(Settings::ADMIN_CREATED))) {
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

        $this->em->transactional(function (): void {
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

            $this->commandBus->handle(new SetSettingBoolValue(Settings::ADMIN_CREATED, true));
        });

        $this->user->logout(true);

        $this->redirect('finish');
    }

    /**
     * Otestování připojení ke skautIS, přesměrování na přihlašovací stránku.
     *
     * @throws AbortException
     */
    public function handleCreateAdmin(): void
    {
        if ($this->checkSkautISConnection()) {
            $this->redirect(':Auth:login', ['backlink' => ':Install:Install:admin']);
        }

        $this->flashMessage('install.admin.skautis_access_denied', 'danger');
    }

    /**
     * Zobrazení stránky po úspěšné instalaci.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function renderFinish(): void
    {
        try {
            if (! $this->queryBus->handle(new SettingBoolValueQuery(Settings::ADMIN_CREATED))) {
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
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function renderInstalled(): void
    {
        try {
            if (! $this->queryBus->handle(new SettingBoolValueQuery(Settings::ADMIN_CREATED))) {
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
    private function checkSkautISConnection(): bool
    {
        try {
            $this->skautIs->org->UnitAllRegistryBasic();
        } catch (WsdlException $ex) {
            Debugger::log($ex, ILogger::WARNING);

            return false;
        }

        return true;
    }
}
