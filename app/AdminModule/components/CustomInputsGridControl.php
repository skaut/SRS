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

    public function createComponentGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->customInputRepository->createQueryBuilder('customInput'));

        $grid->addColumnText('name', 'Název');

        $grid->addColumnText('type', 'Typ')
            ->setRenderer(function ($row) {
                return $row->getType();
            });


        $grid->addInlineAdd()->onControlAdd[] = function($container) {
            $container->addText('name', '');
            $container->addText('type', '');
        };

        $p = $this;
        $customInputRepository = $this->customInputRepository;

        $grid->getInlineAdd()->onSubmit[] = function($values) use ($p, $customInputRepository) {
            $customInputRepository->createCheckBox($values['name']);
            $p->flashMessage("Record with values was added! (not really)", 'alert-success');
            $p->redrawControl(); //TODO obnoveni
        };

        //TODO editace, smazani, vyber typu pomoci select
    }
}