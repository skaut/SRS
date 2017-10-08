<?php

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\Structure\Discount;
use App\Model\Structure\DiscountRepository;
use App\Model\Structure\SubeventRepository;
use App\Services\DiscountService;
use Nette\Application\UI;
use Nette\Application\UI\Form;


/**
 * Komponenta s formulářem pro úpravu slevy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DiscountForm extends UI\Control
{
    /**
     * Id upravované slevy.
     * @var int
     */
    public $id;

    /**
     * Upravovaná sleva.
     * @var Discount
     */
    private $discount;

    /**
     * Událost při uložení formuláře.
     */
    public $onDiscountSave;

    /**
     * Událost při chybě podmínky.
     */
    public $onConditionError;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var DiscountRepository */
    private $discountRepository;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var DiscountService */
    private $discountService;


    /**
     * DiscountForm constructor.
     * @param $id
     * @param BaseForm $baseFormFactory
     * @param DiscountRepository $discountRepository
     * @param SubeventRepository $subeventRepository
     * @param DiscountService $discountService
     */
    public function __construct($id, BaseForm $baseFormFactory, DiscountRepository $discountRepository,
                                SubeventRepository $subeventRepository, DiscountService $discountService)
    {
        parent::__construct();

        $this->baseFormFactory = $baseFormFactory;
        $this->discountRepository = $discountRepository;
        $this->subeventRepository = $subeventRepository;
        $this->discountService = $discountService;

        $this->id = $id;
        $this->discount = $this->discountRepository->findById($id);
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/discount_form.latte');

        $this->template->subevents = $this->subeventRepository->findAll();

        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     * @return Form
     */
    public function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addHidden('condition');

        $form->addTextArea('conditionText', 'admin.configuration.discounts_condition')
            ->setAttribute('readonly', TRUE);

        $form->addText('discount', 'admin.configuration.discounts_discount')
            ->addRule(Form::FILLED, 'admin.configuration.discounts_discount_empty')
            ->addRule(Form::INTEGER, 'admin.configuration.discounts_discount_format');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setAttribute('class', 'btn btn-warning');


        if ($this->discount) {
            $form->setDefaults([
                'id' => $this->id,
                'conditionText' => $this->discountService->convertConditionToText($this->discount->getDiscountCondition()),
                'condition' => $this->discount->getDiscountCondition(),
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
        $this->id = $values['id'];

        if ($this->discountService->validateCondition(($values['condition']))) {
            if (!$this->id)
                $this->discount = new Discount();
            else
                $this->discount = $this->discountRepository->findById($this->id);

            $this->discount->setDiscountCondition($values['condition']);
            $this->discount->setDiscount($values['discount']);

            $this->discountRepository->save($this->discount);

            $this->onDiscountSave($this);
        }
        else
            $this->onConditionError($this);
    }
}
