<?php

namespace App\AdminModule\MailingModule\Presenters;


use App\AdminModule\MailingModule\Forms\SendForm;
use Nette\Forms\Form;

class SendPresenter extends MailingBasePresenter
{
    /**
     * @var SendForm
     * @inject
     */
    public $sendFormFactory;

    protected function createComponentSendForm($name)
    {
        $form = $this->sendFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.mailing.send_sent', 'success');

            $this->redirect('this');
        };

        return $form;
    }
}