<?php

namespace App\WebModule\Forms;

use Nette\Application\UI\Form;

class PersonalDetailsFormFactory
{
    /**
     * @var BaseFormFactory
     */
    private $baseFormFactory;

    public function __construct(BaseFormFactory $baseFormFactory)
    {
        $this->baseFormFactory = $baseFormFactory;
    }

    public function create($userIsMember)
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $sexOptions = [
            'male' => $form->getTranslator()->translate('common.sex.male'),
            'female' => $form->getTranslator()->translate('common.sex.female')
        ];

        $inputSex = $form->addRadioList('sex', $form->getTranslator()->translate('web.profile.sex'), $sexOptions)
            ->getSeparatorPrototype()->setName(null);

        $inputFirstName = $form->addText('firstName', $form->getTranslator()->translate('web.profile.firstname'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('web.profile.firstname_empty'));

        $inputLastName = $form->addText('lastName', $form->getTranslator()->translate('web.profile.lastname'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('web.profile.lastname_empty'));

        $inputNickName = $form->addText('nickName', $form->getTranslator()->translate('web.profile.nickname'));

//        $inputBirthdate = $form->addDate('birthdate', $form->getTranslator()->translate('web.profile.birthdate'))
//            ->addRule(Form::FILLED, $form->getTranslator()->translate('web.profile.birthdate_empty'));

        if ($userIsMember) {
            $inputSex->setDisabled();
            $inputFirstName->setDisabled();
            $inputLastName->setDisabled();
            $inputNickName->setDisabled();
            //$inputBirthdate->setDisabled();
        }

        $form->addText('street', $form->getTranslator()->translate('web.profile.street'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('web.profile.street_empty'));

        $form->addText('city', $form->getTranslator()->translate('web.profile.city'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('web.profile.city_empty'));

        $form->addText('postcode', $form->getTranslator()->translate('web.profile.postcode'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('web.profile.postcode_empty'));

        $form->addText('state', $form->getTranslator()->translate('web.profile.state'))
            ->addRule(Form::FILLED, $form->getTranslator()->translate('web.profile.state_empty'));

        $form->addSubmit('update_personal_details', $form->getTranslator()->translate('web.profile.update_personal_details'));

        return $form;
    }
}
