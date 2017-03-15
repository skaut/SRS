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


    protected function createComponentSendForm()
    {
        $form = $this->sendFormFactory->create();

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($this->sendFormFactory->mailSuccess)
                $this->flashMessage('admin.mailing.send_sent', 'success');
            else
                $this->flashMessage('admin.mailing.send_error', 'danger');

            $this->redirect('this');
        };

        return $form;
    }
}