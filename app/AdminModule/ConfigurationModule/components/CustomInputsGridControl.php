<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Kdyby\Translation\Translator;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridColumnStatusException;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu vlastních polí přihlášky.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class CustomInputsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var CustomInputRepository */
    private $customInputRepository;


    public function __construct(Translator $translator, CustomInputRepository $customInputRepository)
    {
        parent::__construct();

        $this->translator            = $translator;
        $this->customInputRepository = $customInputRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render() : void
    {
        $this->template->render(__DIR__ . '/templates/custom_inputs_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @throws DataGridColumnStatusException
     * @throws DataGridException
     */
    public function createComponentCustomInputsGrid(string $name) : void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setSortable();
        $grid->setSortableHandler('customInputsGrid:sort!');
        $grid->setDataSource($this->customInputRepository->createQueryBuilder('i')->orderBy('i.position'));
        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.configuration.custom_inputs_name');

        $grid->addColumnText('type', 'admin.configuration.custom_inputs_type')
            ->setRenderer(function (CustomInput $row) {
                return $this->translator->translate('admin.common.custom_' . $row->getType());
            });

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
            ->setRenderer(function (CustomInput $row) {
                return $row->getType() === CustomInput::SELECT ? $row->getOptions() : null;
            });

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
    }

    /**
     * Zpracuje odstranění vlastního pole.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function handleDelete(int $id) : void
    {
        $input = $this->customInputRepository->findById($id);
        $this->customInputRepository->remove($input);

        $this->getPresenter()->flashMessage('admin.configuration.custom_inputs_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Přesune vlastní pole s id $item_id mezi $prev_id a $next_id.
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function handleSort($item_id, $prev_id, $next_id) : void
    {
        $this->customInputRepository->sort((int) $item_id, (int) $prev_id, (int) $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.custom_inputs_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    /**
     * Změní povinnost pole.
     * @throws NonUniqueResultException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws AbortException
     */
    public function changeMandatory(int $id, bool $mandatory) : void
    {
        $customInput = $this->customInputRepository->findById($id);
        $customInput->setMandatory($mandatory);
        $this->customInputRepository->save($customInput);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.custom_inputs_changed_mandatory', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->redrawItem($id);
        } else {
            $this->redirect('this');
        }
    }
}
