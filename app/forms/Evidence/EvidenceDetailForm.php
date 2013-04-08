<?php
/**
 * Date: 18.2.13
 * Time: 10:16
 * Author: Michal Májský
 */
namespace SRS\Form\Evidence;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

/**
 * Formular pro upravu udaju ucastnika na detailu
 */
class EvidenceDetailForm extends \SRS\Form\EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $configParams)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addCheckbox('paid', 'Zaplatil');
        $this->addCheckbox('attended', 'Přítomen');
        $this->addSelect('paymentMethod', 'Platební metoda')->setItems($configParams['payment_methods'])->setPrompt('Nezadáno');
        $this->addText('paymentDate', 'Datum zaplacení')
            ->addCondition(FORM::FILLED)
            ->addRule(FORM::PATTERN, 'Datum zaplacení není ve správném tvaru', \SRS\Helpers::DATE_PATTERN);

        $this->addCheckbox('incomeProofPrinted', 'Příjmový doklad vytištěn');
        $this->addSubmit('submit', 'Uložit')->getControlPrototype()->class('btn btn-primary pull-right');
        $this->onSuccess[] = callback($this, 'submitted');
        $this['paymentDate']->getControlPrototype()->class('datepicker');

    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
        $user->setProperties($values, $this->presenter->context->database);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Záznam uložen', 'success');
        $this->presenter->redirect('this');
    }
}
