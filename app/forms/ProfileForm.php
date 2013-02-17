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

class ProfileForm extends EntityForm
{
    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);
        $this->configure();
        $this->onSuccess[] = callback($this, 'submitted');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $role = $this->presenter->context->database->getRepository('\SRS\Model\Acl\Role')->find($values['role']);
        $user = $this->presenter->context->database->getRepository('\SRS\Model\User')->find($values['id']);
        $user->setProperties($values, $this->presenter->context->database);
        $user->approved = $role->approvedAfterRegistration;
        $this->presenter->context->database->flush();
        $this->presenter->flashMessage('Přihláška odeslána', 'success');
        $this->presenter->flashMessage('Pro další používání webu se znovu přihlašte přes skautIS', 'info');
        $this->presenter->user->logout(true);
        $this->presenter->redirect(':Auth:logout');

    }

    protected function configure()
    {
        $this->setFields();
        $this->addSubmit('submit', 'Aktualizovat');
    }


    protected function setFields() {
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
        $this->addText('street', 'Ulice:')
            ->addRule(Form::FILLED, 'Zadejte Ulici');
        $this->addText('city', 'Město:')
            ->addRule(Form::FILLED, 'Zadejte Město');
        $this->addText('postcode', 'PSČ:')
            ->addRule(Form::FILLED, 'Zadejte PSČ');
    }


}
