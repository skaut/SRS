<?php
/**
 * Date: 7.12.12
 * Time: 9:30
 * Author: Michal Májský
 */
namespace SRS\Form;

use Nette\Application\UI\Form,
    Nette\ComponentModel\IContainer;


/**
 * Formular pro editaci uzivatelskych osobnich udaju
 */
class ProfileForm extends EntityForm
{
    private $database;

    /**
     * @var \SRS\Model\skautIS
     */
    private $skautIS;

    public function __construct(IContainer $parent = NULL, $name = NULL, $translator, $database, $user, $skautIS)
    {
        parent::__construct($parent, $name);

        $this->database = $database;
        $this->skautIS = $skautIS;

        $this->setTranslator($translator);

        $this->addHidden('id');

        $inputSex = $this->addSelect('sex', 'Pohlaví', ['male' => 'Muž', 'female' => 'Žena'])
            ->addRule(Form::FILLED, 'Zadejte pohlaví');

        $inputFirstName = $this->addText('firstName', 'Jméno')
            ->addRule(Form::FILLED, 'Zadejte jméno');

        $inputLastName = $this->addText('lastName', 'Příjmení')
            ->addRule(Form::FILLED, 'Zadejte příjmení');

        $inputNickName = $this->addText('nickName', 'Přezdívka');

        $inputBirthdate = $this->addText('birthdate', 'Datum narození')
            ->addRule(Form::FILLED, 'Zadejte datum narození');

        if ($user->member) {
            $inputSex->setDisabled();
            $inputFirstName->setDisabled();
            $inputLastName->setDisabled();
            $inputNickName->setDisabled();
            $inputBirthdate->setDisabled();
        }
        else {
            $inputBirthdate->getControlPrototype()->class('datepicker-birthdate');
        }

//        @TODO - pro aktualizaci emailu je treba udelit zvlastni pravo, ktere SRS zatim nema
//        $this->addText('email', 'Email:')
//            ->addRule(Form::FILLED, 'Zadejte e-mailovou adresu')
//            ->addRule(Form::EMAIL, 'E-mail není ve správném tvaru');

        $this->addText('street', 'Ulice')
            ->addRule(Form::FILLED, 'Zadejte Ulici');

        $this->addText('city', 'Město')
            ->addRule(Form::FILLED, 'Zadejte Město');

        $this->addText('postcode', 'PSČ')
            ->addRule(Form::FILLED, 'Zadejte PSČ');

        $this->addText('state', 'Stát')
            ->addRule(Form::FILLED, 'Zadejte stát');

        $this->addSubmit('sync_with_skautis', 'Aktualizovat data a sesynchronizovat se skautIS');

        $this->onSuccess[] = callback($this, 'submitted');
    }

    public function submitted()
    {
        $values = $this->getValues();

        $user = $this->database->getRepository('\SRS\Model\User')->find($values['id']);

        $user->setProperties($values, $this->presenter->context->database);

        $user->displayName = "{$user->lastName} {$user->firstName}";
        if ($user->nickName != '')
            $user->displayName .= " ({$user->nickName})";

        $this->database->flush();

        $skautISPerson = $this->skautIS->getPerson($this->presenter->context->user->identity->token, $user->skautISPersonId);
        $updatedSkautISPerson = \SRS\Factory\UserFactory::updateSkautISPerson($user, $skautISPerson);
        try {
            $this->skautIS->updatePerson($updatedSkautISPerson, $this->presenter->context->user->identity->token);
        } catch (\SoapFault $e) {
            $this->presenter->flashMessage('Synchronizace se skautIS se nepodařila', 'error');
        }

        $this->presenter->flashMessage('Data aktualizována', 'success');
        $this->presenter->redirect('this');
    }
}
