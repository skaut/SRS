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

    private $em;

    /**
     * @var \SRS\Model\skautIS
     */
    private $skautIS;

    public function __construct(IContainer $parent = NULL, $name = NULL)
    {
        parent::__construct($parent, $name);
        $this->configure();
        $this->onSuccess[] = callback($this, 'submitted');
    }

    public function submitted()
    {
        $values = $this->getValues();
        $user = $this->em->getRepository('\SRS\Model\User')->find($values['id']);
        $user->setProperties($values, $this->presenter->context->database);
        $user->displayName = "{$user->lastName} {$user->firstName}";
        if ($user->nickName != '') $user->displayName .=" ({$user->nickName})";
        $this->em->flush();

        $skautISPerson = $this->skautIS->getPerson($this->presenter->context->user->identity->token, $user->skautISPersonId);
        $updatedSkautISPerson = \SRS\Factory\UserFactory::updateSkautISPerson($user, $skautISPerson);
        try {
        $this->skautIS->updatePerson($updatedSkautISPerson, $this->presenter->context->user->identity->token);
        }
        catch (\SoapFault $e)
        {
            $this->presenter->flashMessage('Synchronizace se skautIS se nepodařila', 'error');
        }
        $this->presenter->flashMessage('Data aktualizována', 'success');
        $this->presenter->redirect('this');


    }

    protected function configure()
    {
        $this->setFields();
        $this->addSubmit('submit', 'Aktualizovat data a sesynchronizovat se skautIS');
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
        $this->addText('state', 'Stát')
            ->addRule(Form::FILLED, 'Zadejte stát');
    }

    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param \SRS\Model\skautIS $skautIS
     */
    public function inject($em, $skautIS) {
        $this->em = $em;
        $this->skautIS = $skautIS;

    }


}
