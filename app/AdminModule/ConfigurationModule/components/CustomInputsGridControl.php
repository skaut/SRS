<?php

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Ublaboo\DataGrid\DataGrid;

class CustomInputsGridControl extends Control
{
    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var CustomInputRepository
     */
    private $customInputRepository;

    public function __construct(Translator $translator, CustomInputRepository $customInputRepository)
    {
        $this->translator = $translator;
        $this->customInputRepository = $customInputRepository;
    }

    public function render()
    {
        $this->template->render(__DIR__ . '/templates/custom_inputs_grid.latte');
    }

    public function createComponentCustomInputsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setSortable();
        $grid->setSortableHandler('customInputsGrid:sort!');
        $grid->setDataSource($this->customInputRepository->createQueryBuilder('i')->orderBy('i.position'));

        $grid->setPagination(false);

        $grid->addColumnText('name', 'admin.configuration.custom_inputs_name');

        $grid->addColumnText('type', 'admin.configuration.custom_inputs_type')
            ->setRenderer(function ($row) {
                return $this->translator->translate('admin.common.custom_' . $row->getType());
            });

        $customInputTypesChoices = $this->prepareCustomInputTypesChoices();

        $grid->addInlineAdd()->onControlAdd[] = function($container) use($customInputTypesChoices) {
            $container->addText('name', '');
            $container->addSelect('type', '', $customInputTypesChoices);
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) {
            $container->addText('name', '');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container->setDefaults([
                'name' => $item->getName()
            ]);
        };
        $grid->getInlineEdit()->setShowNonEditingColumns();
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirm('admin.configuration.application_input_delete_confirm', 'name');
    }

    public function add($values) {
        $p = $this->getPresenter();

        $name = $values['name'];

        if (!$name) {
            $p->flashMessage('admin.configuration.application_input_name_empty', 'danger');
        }
        else {
            switch ($values['type']) {
                case 'text':
                    $this->customInputRepository->addText($name);
                    break;

                case 'checkbox':
                    $this->customInputRepository->addCheckBox($name);
                    break;
            }
            $p->flashMessage('admin.configuration.application_input_added', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function edit($id, $values)
    {
        $p = $this->getPresenter();

        $name = $values['name'];

        if (!$name) {
            $p->flashMessage('admin.configuration.application_input_name_empty', 'danger');
        }
        else {
            $this->customInputRepository->renameInput($id, $name);
            $p->flashMessage('admin.configuration.application_input_edited', 'success');
        }

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
        } else {
            $this->redirect('this');
        }
    }

    public function handleDelete($id)
    {
        $this->customInputRepository->removeInput($id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.application_input_deleted', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function handleSort($item_id, $prev_id, $next_id)
    {
        $this->customInputRepository->changePosition($item_id, $prev_id, $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.application_inputs_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    private function prepareCustomInputTypesChoices() {
        $choices = [];
        foreach (CustomInput::$types as $type)
            $choices[$type] = $this->translator->translate('admin.common.custom_' . $type);
        return $choices;
    }
}