<?php

declare(strict_types=1);

namespace App\AdminModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Services\AclService;
use App\Utils\Helpers;
use Doctrine\ORM\OptimisticLockException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Throwable;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu rolí.
 */
class RolesGridControl extends Control
{
    public function __construct(
        private readonly Translator $translator,
        private readonly AclService $aclService,
        private readonly RoleRepository $roleRepository,
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/roles_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentRolesGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->roleRepository->createQueryBuilder('r'));
        $grid->setDefaultSort(['name' => 'ASC']);
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.acl.roles_name');

        $grid->addColumnText('systemRole', 'admin.acl.roles_system')
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
            ->setRendererOnCondition(fn (Role $row) => $this->translator->translate('admin.acl.roles_fee_from_subevents'), static fn (Role $row) => $row->getFee() === null);

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
        $grid->allowRowsAction('delete', static fn (Role $item) => ! $item->isSystemRole());

        return $grid;
    }

    /**
     * Zpracuje odstranění role.
     *
     * @throws AbortException
     * @throws Throwable
     */
    public function handleDelete(int $id): void
    {
        $role = $this->roleRepository->findById($id);

        $p = $this->getPresenter();

        if ($role->getUsers()->isEmpty()) {
            $this->aclService->removeRole($role);
            $p->flashMessage('admin.acl.roles_deleted', 'success');
        } else {
            $p->flashMessage('admin.acl.roles_deleted_error', 'danger');
        }

        $p->redirect('this');
    }

    /**
     * Změní registrovatelnost role.
     *
     * @throws AbortException
     * @throws OptimisticLockException
     */
    public function changeRegisterable(string $id, string $registerable): void
    {
        $role = $this->roleRepository->findById((int) $id);

        $role->setRegisterable((bool) $registerable);
        $this->aclService->saveRole($role);

        $p = $this->getPresenter();
        $p->flashMessage('admin.acl.roles_changed_registerable', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('rolesGrid')->redrawItem($id);
        } else {
            $p->redirect('this');
        }
    }
}
