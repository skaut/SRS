<?php

namespace App\AdminModule\Components;

use App\Model\Settings\CustomInput\CustomInputRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
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

        $grid->addColumnText('name', 'Název');

        $grid->addColumnText('type', 'Typ')
            ->setRenderer(function ($row) {
                return $row->getType();
            });


        $grid->addInlineAdd()->onControlAdd[] = function($container) {
            $container->addText('name', '');
            $container->addSelect('type', '', ['text' => 'Textové pole', 'checkbox' => 'Zaškrtávací pole']);
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()
            ->onControlAdd[] = function($container) {
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
            ->setTitle('Odstranit')
            ->setClass('btn btn-xs btn-danger ajax')
            ->setConfirm('Opravdu chcete odstranit pole "%s"?', 'name');
    }

    public function add($values) {
        switch ($values['type']) {
            case 'text':
                $this->customInputRepository->createText($values['name']);
                break;

            case 'checkbox':
                $this->customInputRepository->createCheckBox($values['name']);
                break;
        }

        $p = $this->getPresenter();
        $p->flashMessage("Pole bylo úspěšně přidáno.", 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }

    public function edit($id, $values)
    {
        $this->customInputRepository->renameInput($id, $values['name']);

        $p = $this->getPresenter();
        $p->flashMessage("Pole bylo úspěšně upraveno.", 'success');

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
        $p->flashMessage("Pole bylo úspěšně odstraněno.", 'success');

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
        $p->flashMessage("Pořadí polí bylo úspěšně upraveno.", 'success');

        if ($p->isAjax()) {
            $p->redrawControl('flashes');
            $this['customInputsGrid']->reload();
        } else {
            $this->redirect('this');
        }
    }
}