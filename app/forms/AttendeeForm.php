<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 7.12.12
 * Time: 9:30
 * To change this template use File | Settings | File Templates.
 */
namespace SRS\Form;

use Nette\Application\UI,
    Nette\Diagnostics\Debugger,
    Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;

class AttendeeForm extends EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL, $roles)
    {
        parent::__construct($parent, $name);

        $this->addHidden('id');
        $this->addSelect('sex', 'Pohlaví:')->setItems(array('male' => 'Muž', 'female' => 'Žena'))
            ->addRule(Form::FILLED, 'Zadejte pohlaví');
        $this->addText('firstName', 'Jméno:')
            ->addRule(Form::FILLED, 'Zadejte jméno');
        $this->addText('lastName', 'Příjmení:')
            ->addRule(Form::FILLED, 'Zadejte příjmení');
        $this->addText('nickName', 'Přezdívka:');
        $this->addText('birthdate', 'Datum narození:')
            ->addRule(Form::FILLED, 'Zadejte datum narození')->getControlPrototype()->class('datepicker');
        $this->addText('email', 'Email:')
            ->addRule(Form::FILLED, 'Zadejte e-mailovou adresu')
            ->addRule(Form::EMAIL, 'E-mail není ve správném tvaru');
        $this->addSelect('role', 'Přihlásit jako:')->setItems($roles)
             ->addRule(Form::FILLED, 'Vyplňte roli');
        $this->addCheckbox('agreement', 'Souhlasím, že uvedené údaje budou poskytnuty lektorům pro účely semináře')
            ->addRule(Form::FILLED, 'Musíte souhlasit s poskytnutím údajů');
        $this->addSubmit('submit', 'Přihlásit na seminář');


        $this->onSuccess[] = callback($this, 'submitted');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
        $user->setProperties($values, $this->presenter->context->database);
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Přihláška odeslána', 'success');
        $this->presenter->flashMessage('Pro další používání webu se znovu přihlašte přes skautIS', 'info');
        $this->presenter->user->logout(true);
        $this->presenter->redirect(':Auth:logout');

    }

}
