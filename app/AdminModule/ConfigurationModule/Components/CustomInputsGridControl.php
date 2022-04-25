<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Acl\Role;
use App\Model\CustomInput\CustomInput;
use App\Model\CustomInput\CustomMultiSelect;
use App\Model\CustomInput\CustomSelect;
use App\Model\CustomInput\Repositories\CustomInputRepository;
use App\Services\AclService;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\ORMException;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

use function count;

/**
 * Komponenta pro správu vlastních polí přihlášky.
 */
class CustomInputsGridControl extends Control
{
    public function __construct(
        private Translator $translator,
        private CustomInputRepository $customInputRepository,
        private AclService $aclService
    ) {
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/custom_inputs_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentCustomInputsGrid(string $name): DataGrid
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setSortable();
        $grid->setSortableHandler('customInputsGrid:sort!');
        $grid->setDataSource($this->customInputRepository->createQueryBuilder('i')->orderBy('i.position'));
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.configuration.custom_inputs_name');

        $grid->addColumnText('roles', 'admin.configuration.custom_inputs_roles', 'rolesText')
            ->setRendererOnCondition(
                fn () => $this->translator->translate('admin.configuration.custom_inputs_roles_all'),
                fn (CustomInput $input) => count($this->aclService->getRolesWithoutRolesOptions([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED])) === $input->getRoles()->count()
            );

        $grid->addColumnText('type', 'admin.configuration.custom_inputs_type')
            ->setRenderer(fn (CustomInput $input) => $this->translator->translate('admin.common.custom_' . $input->getType()));

        $columnMandatory = $grid->addColumnStatus('mandatory', 'admin.configuration.custom_inputs_mandatory');
        $columnMandatory
            ->addOption(false, 'admin.configuration.custom_inputs_mandatory_voluntary')
            ->setClass('btn-primary')
            ->endOption()
            ->addOption(true, 'admin.configuration.custom_inputs_mandatory_mandatory')
            ->setClass('btn-danger')
            ->endOption()
            ->onChange[] = [$this, 'changeMandatory'];

        $grid->addColumnText('options', 'admin.configuration.custom_inputs_options')
            ->setRenderer(static fn (CustomInput $input) => $input instanceof CustomSelect || $input instanceof CustomMultiSelect ? $input->getOptionsText() : null);

        $grid->addToolbarButton('Application:add')
            ->setIcon('plus')
            ->setTitle('admin.common.add');

        $grid->addAction('edit', 'admin.common.edit', 'Application:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.configuration.custom_inputs_delete_confirm'),
            ]);

        return $grid;
    }

    /**
     * Zpracuje odstranění vlastního pole.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $input = $this->customInputRepository->findById($id);
        $this->customInputRepository->remove($input);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.custom_inputs_deleted', 'success');
        $p->redirect('this');
    }

    /**
     * Přesune vlastní pole s id $item_id mezi $prev_id a $next_id.
     *
     * @throws ORMException
     * @throws AbortException
     */
    public function handleSort(?string $item_id, ?string $prev_id, ?string $next_id): void
    {
        $this->customInputRepository->sort((int) $item_id, (int) $prev_id, (int) $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.custom_inputs_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('customInputsGrid')->reload();
        } else {
            $p->redirect('this');
        }
    }

    /**
     * Změní povinnost pole.
     *
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws AbortException
     */
    public function changeMandatory(string $id, string $mandatory): void
    {
        $customInput = $this->customInputRepository->findById((int) $id);
        $customInput->setMandatory((bool) $mandatory);
        $this->customInputRepository->save($customInput);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.custom_inputs_changed_mandatory', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this->getComponent('customInputsGrid')->redrawItem($id);
        } else {
            $p->redirect('this');
        }
    }
}
