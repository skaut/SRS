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

    public function create($user)
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id');

        $sexOptions = [
            'male' => 'common.sex.male',
            'female' => 'common.sex.female'
        ];

        $inputSex = $form->addRadioList('sex', 'web.profile.sex', $sexOptions);
        $inputSex->getSeparatorPrototype()->setName(null);

        $inputFirstName = $form->addText('firstName', 'web.profile.firstname')
            ->addRule(Form::FILLED, 'web.profile.firstname_empty');

        $inputLastName = $form->addText('lastName', 'web.profile.lastname')
            ->addRule(Form::FILLED, 'web.profile.lastname_empty');

        $inputNickName = $form->addText('nickName', 'web.profile.nickname');

        $inputBirthdate = $form->addDatePicker('birthdate', 'web.profile.birthdate')
            ->addRule(Form::FILLED, 'web.profile.birthdate_empty');

        if ($user->isMember()) {
            $inputSex->setDisabled();
            $inputFirstName->setDisabled();
            $inputLastName->setDisabled();
            $inputNickName->setDisabled();
            $inputBirthdate->setDisabled();
        }

        $form->addText('street', 'web.profile.street')
            ->addRule(Form::FILLED, 'web.profile.street_empty')
            ->addRule(Form::PATTERN, 'web.profile.street_format', '^(.*[^0-9]+) (([1-9][0-9]*)/)?([1-9][0-9]*[a-cA-C]?)$');

        $form->addText('city', 'web.profile.city')
            ->addRule(Form::FILLED, 'web.profile.city_empty');

        $form->addText('postcode', 'web.profile.postcode')
            ->addRule(Form::FILLED, 'web.profile.postcode_empty')
            ->addRule(Form::PATTERN, 'web.profile.postcode_format', '^\d{3} ?\d{2}$');

        $form->addText('state', 'web.profile.state')
            ->addRule(Form::FILLED, 'web.profile.state_empty');

        $form->addSubmit('submit', 'web.profile.update_personal_details');

        return $form;
    }
}
