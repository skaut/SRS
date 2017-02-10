<?php

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Settings\CustomInput\CustomCheckbox;
use App\Model\Settings\CustomInput\CustomInput;
use App\Model\Settings\CustomInput\CustomInputRepository;
use App\Model\Settings\CustomInput\CustomText;
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


        $customInputTypesOptions = $this->prepareCustomInputTypesOptions();

        $grid->addInlineAdd()->onControlAdd[] = function($container) use($customInputTypesOptions) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.configuration.application_input_name_empty');
            $container->addSelect('type', '', $customInputTypesOptions);
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function($container) {
            $container->addText('name', '')
                ->addRule(Form::FILLED, 'admin.configuration.application_input_name_empty');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function($container, $item) {
            $container->setDefaults([
                'name' => $item->getName()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];


        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.configuration.application_input_delete_confirm')
            ]);
    }

    public function add($values) {
        switch ($values['type']) {
            case 'text':
                $input = new CustomText();
                break;
            case 'checkbox':
                $input = new CustomCheckbox();
                break;
        }

        $input->setName($values['name']);

        $this->customInputRepository->save($input);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.application_input_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function edit($id, $values)
    {
        $input = $this->customInputRepository->findById($id);

        $input->setName($values['name']);

        $this->customInputRepository->save($input);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.application_input_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
        } else {
            $this->redirect('this');
        }
    }

    public function handleDelete($id)
    {
        $input = $this->customInputRepository->findById($id);
        $this->customInputRepository->remove($input);

        $this->getPresenter()->flashMessage('admin.configuration.application_input_deleted', 'success');

        $this->redirect('this');
    }

    public function handleSort($item_id, $prev_id, $next_id)
    {
        $this->customInputRepository->sort($item_id, $prev_id, $next_id);

        $p = $this->getPresenter();
        $p->flashMessage('admin.configuration.application_inputs_order_saved', 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    private function prepareCustomInputTypesOptions() {
        $options = [];
        foreach (CustomInput::$types as $type)
            $options[$type] = 'admin.common.custom_' . $type;
        return $options;
    }
}