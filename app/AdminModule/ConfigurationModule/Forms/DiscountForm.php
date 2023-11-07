<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Structure\Discount;
use App\Model\Structure\Repositories\DiscountRepository;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Services\DiscountService;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Komponenta s formulářem pro úpravu slevy.
 */
class DiscountForm extends UI\Control
{
    /**
     * Upravovaná sleva.
     */
    private Discount|null $discount;

    /**
     * Událost při uložení formuláře.
     *
     * @var callable[]
     */
    public array $onSave = [];

    /**
     * Událost při chybě podmínky.
     *
     * @var callable[]
     */
    public array $onConditionError = [];

    /** @param int $id Id upravované slevy. */
    public function __construct(
        public int $id,
        private readonly BaseFormFactory $baseFormFactory,
        private readonly DiscountRepository $discountRepository,
        private readonly SubeventRepository $subeventRepository,
        private readonly DiscountService $discountService,
    ) {
        $this->discount = $this->discountRepository->findById($id);
    }

    /**
     * Vykreslí komponentu.
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/discount_form.latte');

        $this->template->subevents = $this->subeventRepository->findAll();

        $this->template->render();
    }

    public function renderScripts(): void
    {
        $this->template->setFile(__DIR__ . '/templates/discount_form_scripts.latte');
        $this->template->render();
    }

    /**
     * Vytvoří formulář.
     */
    public function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addHidden('condition');

        $form->addTextArea('conditionText', 'admin.configuration.discounts_condition')
            ->setHtmlAttribute('readonly');

        $form->addInteger('discount', 'admin.configuration.discounts_discount')
            ->addRule(Form::FILLED, 'admin.configuration.discounts_discount_empty')
            ->addRule(Form::INTEGER, 'admin.configuration.discounts_discount_format');

        $form->addSubmit('submit', 'admin.common.save');

        $form->addSubmit('cancel', 'admin.common.cancel')
            ->setValidationScope([])
            ->setHtmlAttribute('class', 'btn btn-warning');

        if ($this->discount) {
            $form->setDefaults([
                'id' => $this->id,
                'conditionText' => $this->discountService->convertConditionToText($this->discount->getDiscountCondition()),
                'condition' => $this->discount->getDiscountCondition(),
                'discount' => $this->discount->getDiscount(),
            ]);
        }

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $this->id = (int) $values->id;

        if ($this->discountService->validateCondition($values->condition)) {
            if (! $this->id) {
                $this->discount = new Discount();
            } else {
                $this->discount = $this->discountRepository->findById($this->id);
            }

            $this->discount->setDiscountCondition($values->condition);
            $this->discount->setDiscount($values->discount);

            $this->discountRepository->save($this->discount);

            $this->onSave();
        } else {
            $this->onConditionError();
        }
    }
}
