<?php

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Enums\ConditionOperator;
use App\Model\Structure\Discount;
use App\Model\Structure\DiscountRepository;
use App\Model\Structure\SubeventRepository;
use App\Services\DiscountService;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Forms\Form;
use Nette\Utils\Html;
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

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var DiscountService */
    private $discountService;


    /**
     * DiscountsGridControl constructor.
     * @param Translator $translator
     * @param DiscountRepository $discountRepository
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(Translator $translator, DiscountRepository $discountRepository,
                                SubeventRepository $subeventRepository, DiscountService $discountService)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->discountRepository = $discountRepository;
        $this->subeventRepository = $subeventRepository;
        $this->discountService = $discountService;
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


        $grid->addColumnText('discountCondition', 'admin.configuration.discounts_condition')
            ->setRenderer(function ($row) {
                if ($this->discountService->validateCondition($row->getDiscountCondition()))
                    return $this->discountService->convertConditionToText($row->getDiscountCondition());
                else
                    return Html::el('span')
                        ->style('color: red')
                        ->setText($this->translator->translate('admin.configuration.discounts_invalid_condition'));
            });

        $grid->addColumnText('discount', 'admin.configuration.discounts_discount');


        $grid->addToolbarButton('Discounts:add')
            ->setIcon('plus');

        $grid->addAction('detail', 'admin.common.edit', 'Discounts:edit');

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.configuration.discounts_delete_confirm')
            ]);
    }

    /**
     * Zpracuje odstranění slevy.
     * @param $id
     */
    public function handleDelete($id)
    {
        $discount = $this->discountRepository->findById($id);
        $this->discountRepository->remove($discount);

        $this->getPresenter()->flashMessage('admin.configuration.discounts_deleted', 'success');

        $this->redirect('this');
    }
}
