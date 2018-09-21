<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

use App\Model\ACL\RoleRepository;
use App\Model\Program\ProgramRepository;
use App\Model\User\UserRepository;
use App\Services\ProgramService;
use App\Utils\Helpers;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu rolí.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class RolesGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var ProgramRepository */
    private $programRepository;

    /** @var ProgramService */
    private $programService;


    public function __construct(
        Translator $translator,
        RoleRepository $roleRepository,
        UserRepository $userRepository,
        ProgramRepository $programRepository,
        ProgramService $programService
    ) {
        parent::__construct();

        $this->translator        = $translator;
        $this->roleRepository    = $roleRepository;
        $this->userRepository    = $userRepository;
        $this->programRepository = $programRepository;
        $this->programService    = $programService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->render(__DIR__ . '/templates/roles_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentRolesGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->roleRepository->createQueryBuilder('r'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.acl.roles_name');

        $grid->addColumnText('system', 'admin.acl.roles_system')
            ->setReplacement([
                '0' => $this->translator->translate('admin.common.no'),
                '1' => $this->translator->translate('admin.common.yes'),
            ]);

        $grid->addColumnStatus('registerable', 'admin.acl.roles_registerable')
            ->addOption(false, 'admin.acl.roles_registerable_nonregisterable')
            ->setClass('btn-danger')
            ->endOption()
            ->addOption(true, 'admin.acl.roles_registerable_registerable')
            ->setClass('btn-success')
            ->endOption()
            ->onChange[] = [$this, 'changeRegisterable'];

        $grid->addColumnDateTime('registerableFrom', 'admin.acl.roles_registerable_from')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $grid->addColumnDateTime('registerableTo', 'admin.acl.roles_registerable_to')
            ->setFormat(Helpers::DATETIME_FORMAT);

        $grid->addColumnText('occupancy', 'admin.acl.roles_occupancy', 'occupancy_text');

        $grid->addColumnText('fee', 'admin.acl.roles_fee')
            ->setRendererOnCondition(function ($row) {
                return $this->translator->translate('admin.acl.roles_fee_from_subevents');
            }, function ($row) {
                return $row->getFee() === null;
            });

        $grid->addToolbarButton('Acl:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('test', 'admin.acl.roles_test', 'Acl:test')
            ->setClass('btn btn-xs btn-primary');

        $grid->addAction('edit', 'admin.common.edit', 'Acl:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.acl.roles_delete_confirm'),
            ]);
        $grid->allowRowsAction('delete', function ($item) {
            return ! $item->isSystem();
        });
    }

    /**
     * Zpracuje odstranění role.
     * @throws AbortException
     * @throws \Throwable
     */
    public function handleDelete(int $id) : void
    {
        $role = $this->roleRepository->findById($id);

        if ($role->getUsers()->isEmpty()) {
            $this->roleRepository->remove($role);
            $this->getPresenter()->flashMessage('admin.acl.roles_deleted', 'success');
        } else {
            $this->getPresenter()->flashMessage('admin.acl.roles_deleted_error', 'danger');
        }

        $this->redirect('this');
    }

    /**
     * Změní registrovatelnost role.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function changeRegisterable(int $id, bool $registerable) : void
    {
        $role = $this->roleRepository->findById($id);

        $role->setRegisterable($registerable);
        $this->roleRepository->save($role);

        $p = $this->getPresenter();
        $p->flashMessage('admin.acl.roles_changed_registerable', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['rolesGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
