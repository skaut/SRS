<?php

namespace App\WebModule\Forms;

class AdditionalInformationFormFactory
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

        $form->addTextArea('about', 'web.profile.about_me');

        if ($user->hasDisplayArrivalDepartureRole()) {
            $form->addDateTimePicker('arrival', 'web.profile.arrival');
            $form->addDateTimePicker('departure', 'web.profile.departure');
        }

        $form->addSubmit('submit', 'web.profile.update_additional_information');

        return $form;
    }
}
