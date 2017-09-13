<?php

namespace App\AdminModule\StructureModule\Components;

use App\Model\Enums\ConditionOperator;
use App\Model\Structure\Discount;
use App\Model\Structure\DiscountRepository;
use App\Model\Structure\SubeventRepository;
use Kdyby\Translation\Translator;
use Nette\Application\UI\Control;
use Nette\Forms\Form;
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


    /**
     * DiscountsGridControl constructor.
     * @param Translator $translator
     * @param DiscountRepository $discountRepository
     * @param SubeventRepository $subeventRepository
     */
    public function __construct(Translator $translator, DiscountRepository $discountRepository,
                                SubeventRepository $subeventRepository)
    {
        parent::__construct();

        $this->translator = $translator;
        $this->discountRepository = $discountRepository;
        $this->subeventRepository = $subeventRepository;
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


        $grid->addColumnText('conditionSubevents', 'admin.structure.discounts_condition_subevents')
            ->setRenderer(function ($row) {
                $subevents = [];
                foreach ($row->getConditionSubevents() as $subevent) {
                    $subevents[] = $subevent->getName();
                }
                return implode(", ", $subevents);
            });

        $grid->addColumnText('conditionOperator', 'admin.structure.discounts_condition_operator')
            ->setRenderer(function ($row) {
                return $this->translator->translate('common.condition_operator.' . $row->getConditionOperator());
            });
        $grid->addColumnText('discount', 'admin.structure.discounts_discount');


        $subeventsOptions = $this->subeventRepository->getSubeventsOptions();
        $operatorsOptions = $this->prepareConditionOperatorOptions();

        $grid->addInlineAdd()->onControlAdd[] = function ($container) use ($subeventsOptions, $operatorsOptions) {
            $container->addMultiSelect('conditionSubevents', '', $subeventsOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.structure.discounts_condition_subevents_empty');

            $container->addSelect('conditionOperator', '', $operatorsOptions)
                ->setDefaultValue(ConditionOperator::OPERATOR_AND);

            $container->addText('discount', '')
                ->addRule(Form::FILLED, 'admin.structure.discounts_discount_empty')
                ->addRule(Form::INTEGER, 'admin.structure.discounts_discount_format')
                ->setDefaultValue(0);
        };
        $grid->getInlineAdd()->onSubmit[] = [$this, 'add'];

        $grid->addInlineEdit()->onControlAdd[] = function ($container) use ($subeventsOptions, $operatorsOptions) {
            $container->addMultiSelect('conditionSubevents', '', $subeventsOptions)->setAttribute('class', 'datagrid-multiselect')
                ->addRule(Form::FILLED, 'admin.structure.discounts_condition_subevents_empty');

            $container->addSelect('conditionOperator', '', $operatorsOptions);

            $container->addText('discount', '')
                ->addRule(Form::FILLED, 'admin.structure.discounts_discount_empty')
                ->addRule(Form::INTEGER, 'admin.structure.discounts_discount_format');
        };
        $grid->getInlineEdit()->onSetDefaults[] = function ($container, $item) {
            $container->setDefaults([
                'conditionSubevents' => $this->subeventRepository->findSubeventsIds($item->getConditionSubevents()),
                'conditionOperator' => $item->getConditionOperator(),
                'discount' => $item->getDiscount()
            ]);
        };
        $grid->getInlineEdit()->onSubmit[] = [$this, 'edit'];

        $grid->addAction('delete', '', 'delete!')
            ->setIcon('trash')
            ->setTitle('admin.common.delete')
            ->setClass('btn btn-xs btn-danger')
            ->addAttributes([
                'data-toggle' => 'confirmation',
                'data-content' => $this->translator->translate('admin.structure.discounts_delete_confirm')
            ]);
    }

    /**
     * Zpracuje přidání slevy.
     * @param $values
     */
    public function add($values)
    {
        $discount = new Discount();

        $discount->setConditionSubevents($this->subeventRepository->findSubeventsByIds($values['conditionSubevents']));
        $discount->setConditionOperator($values['conditionOperator']);
        $discount->setDiscount($values['discount']);

        $this->discountRepository->save($discount);

        $this->getPresenter()->flashMessage('admin.structure.discounts_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje úpravu slevy.
     * @param $id
     * @param $values
     */
    public function edit($id, $values)
    {
        $discount = $this->discountRepository->findById($id);

        $discount->setConditionSubevents($this->subeventRepository->findSubeventsByIds($values['conditionSubevents']));
        $discount->setConditionOperator($values['conditionOperator']);
        $discount->setDiscount($values['discount']);

        $this->discountRepository->save($discount);

        $this->getPresenter()->flashMessage('admin.structure.discounts_saved', 'success');

        $this->redirect('this');
    }

    /**
     * Zpracuje odstranění slevy.
     * @param $id
     */
    public function handleDelete($id)
    {
        $discount = $this->discountRepository->findById($id);
        $this->discountRepository->remove($discount);

        $this->getPresenter()->flashMessage('admin.structure.discounts_deleted', 'success');

        $this->redirect('this');
    }

    /**
     * Vrátí operátory podmínky jako možnosti pro select.
     * @return array
     */
    private function prepareConditionOperatorOptions()
    {
        $options = [];
        foreach (ConditionOperator::$operators as $operator)
            $options[$operator] = 'common.condition_operator.' . $operator;
        return $options;
    }
}
