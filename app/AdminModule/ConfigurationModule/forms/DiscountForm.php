<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Structure\Discount;
use App\Model\Structure\DiscountRepository;
use App\Model\Structure\SubeventRepository;
use Nette;
use Nette\Application\UI\Form;


/**
 * Formulář pro úpravu slevy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountForm extends Nette\Object
{
    /**
     * Upravovaná sleva.
     * @var Discount
     */
    private $discount;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var DiscountRepository */
    private $discountRepository;

    /** @var SubeventRepository */
    private $subeventRepository;


    /**
     * DiscountForm constructor.
     * @param BaseForm $baseFormFactory
     * @param DiscountRepository $discountRepository
     */
    public function __construct(BaseForm $baseFormFactory, DiscountRepository $discountRepository,
                                SubeventRepository $subeventRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->discountRepository = $discountRepository;
        $this->subeventRepository = $subeventRepository;
    }

    /**
     * Vytvoří formulář.
     * @param $id
     * @return Form
     */
    public function create($id)
    {
        $this->discount = $this->discountRepository->findById($id);

        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addSelect('conditionOperator', 'admin.configuration.discounts_condition_operator', $this->prepareOperatorOptions());

        $form->addMultiSelect('conditionSubevents', 'admin.configuration.discounts_condition_subevents', $this->prepareSubeventsOptions());

        $form->addText('discount', 'admin.configuration.discounts_discount')
            ->addCondition(Form::FILLED)
            ->addRule(Form::INTEGER, 'admin.configuration.discounts_discount_format');


        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');


        if ($this->discount) {
            $form->setDefaults([
                'id' => $id,
                'conditionOperator' => $this->discount->getConditionOperator(),
                'conditionSubevents' => $this->discount->getConditionSubevents(),
                'discount' => $this->discount->getDiscount()
            ]);
        }


        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     */
    public function processForm(Form $form, \stdClass $values)
    {
        if (!$form['cancel']->isSubmittedBy()) {
            if (!$this->discount)
                $this->discount = new Discount();

            $this->discount->setConditionOperator($values['conditionOperator']);
            $this->discount->setConditionSubevents($this->subeventRepository->findSubeventsByIds($values['conditionSubevents']));
            $this->discount->setDiscount($values['discount']);

            $this->discountRepository->save($this->discount);
        }
    }
}
