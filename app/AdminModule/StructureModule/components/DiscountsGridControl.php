<?php

namespace App\AdminModule\StructureModule\Components;

use App\Model\Structure\DiscountRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Ublaboo\DataGrid\DataGrid;


/**
 * Komponenta pro správu slev.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountsGridControl extends Control
{
    /** @var Translator */
    private $translator;

    /** @var DiscountRepository */
    private $discountRepository;


    /**
     * DiscountsGridControl constructor.
     * @param Translator $translator
     * @param DiscountRepository $discountRepository
     */
    public function __construct(Translator $translator, DiscountRepository $discountRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->discountRepository = $discountRepository;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->render(__DIR__ . '/templates/discounts_grid.latte');
    }

    /**
     * Vytvoří komponentu.
     * @param $name
     */
    public function createComponentDiscountsGrid($name)
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->discountRepository->createQueryBuilder('d'));
        $grid->setPagination(FALSE);


        $grid->addColumnText('conditionSubevents', 'admin.structure.discounts_sondition_subevents');

        $grid->addColumnText('conditionOperator', 'admin.structure.discounts_condition_operator');

        $grid->addColumnText('discount', 'admin.structure.discounts_discount');


        $grid->addAction('edit', 'admin.common.edit', 'Discounts:edit');
    }
}
