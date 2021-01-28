<?php

declare(strict_types=1);

namespace App\AdminModule\ConfigurationModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Structure\Discount;
use App\Model\Structure\Repositories\DiscountRepository;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Services\DiscountService;
use Doctrine\ORM\ORMException;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Komponenta s formulářem pro úpravu slevy.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class DiscountForm extends UI\Control
{
    /**
     * Id upravované slevy.
     */
    public int $id;

    /**
     * Upravovaná sleva.
     */
    private ?Discount $discount;

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

    private BaseFormFactory $baseFormFactory;

    private DiscountRepository $discountRepository;

    private SubeventRepository $subeventRepository;

    private DiscountService $discountService;

    public function __construct(
        int $id,
        BaseFormFactory $baseFormFactory,
        DiscountRepository $discountRepository,
        SubeventRepository $subeventRepository,
        DiscountService $discountService
    ) {
        $this->baseFormFactory    = $baseFormFactory;
        $this->discountRepository = $discountRepository;
        $this->subeventRepository = $subeventRepository;
        $this->discountService    = $discountService;

        $this->id       = $id;
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

    /**
     * Vytvoří formulář.
     */
    public function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $form->addHidden('condition');

        $form->addTextArea('conditionText', 'admin.configuration.discounts_condition')
            ->setHtmlAttribute('readonly', true);

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
     *
     * @throws ORMException
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
