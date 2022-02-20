<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Components;

use App\Model\Structure\Discount;
use App\Model\Structure\Repositories\DiscountRepository;
use App\Services\DiscountService;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nette\Localization\Translator;
use Nette\Utils\Html;
use Ublaboo\DataGrid\DataGrid;
use Ublaboo\DataGrid\Exception\DataGridException;

/**
 * Komponenta pro správu slev.
 */
class DiscountsGridControl extends Control
{
    private Translator $translator;

    private DiscountRepository $discountRepository;

    private DiscountService $discountService;

    public function __construct(
        Translator $translator,
        DiscountRepository $discountRepository,
        DiscountService $discountService
    ) {
        $this->translator         = $translator;
        $this->discountRepository = $discountRepository;
        $this->discountService    = $discountService;
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/discounts_grid.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws DataGridException
     */
    public function createComponentDiscountsGrid(string $name): void
    {
        $grid = new DataGrid($this, $name);
        $grid->setTranslator($this->translator);
        $grid->setDataSource($this->discountRepository->createQueryBuilder('d'));
        $grid->setPagination(false);

        $grid->addColumnText('discountCondition', 'admin.configuration.discounts_condition')
            ->setRenderer(function (Discount $row) {
                if ($this->discountService->validateCondition($row->getDiscountCondition())) {
                    return $this->discountService->convertConditionToText($row->getDiscountCondition());
                }

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
                'data-content' => $this->translator->translate('admin.configuration.discounts_delete_confirm'),
            ]);
    }

    /**
     * Zpracuje odstranění slevy.
     *
     * @throws AbortException
     */
    public function handleDelete(int $id): void
    {
        $discount = $this->discountRepository->findById($id);
        $this->discountRepository->remove($discount);

        $this->getPresenter()->flashMessage('admin.configuration.discounts_deleted', 'success');
        $this->redirect('this');
    }
}
