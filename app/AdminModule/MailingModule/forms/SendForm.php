<?php

namespace App\AdminModule\MailingModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Mailing\Mail;
use App\Model\Mailing\MailRepository;
use App\Model\Mailing\MailToRoles;
use App\Model\Program\Block;
use App\Model\Program\BlockRepository;
use App\Model\Program\CategoryRepository;
use App\Model\Settings\SettingsRepository;
use App\Model\User\UserRepository;
use App\Services\MailService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Mail\SendException;

class SendForm extends Nette\Object
{
    public $mailSuccess;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var MailService */
    private $mailService;

    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(BaseForm $baseFormFactory, MailService $mailService, RoleRepository $roleRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->mailService = $mailService;
        $this->roleRepository = $roleRepository;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $form->addMultiSelect('recipients', 'admin.mailing.send_recipients',
            $this->roleRepository->getRolesWithoutRolesOptionsWithUsersCount([Role::GUEST, Role::UNAPPROVED, Role::NONREGISTERED]))
            ->addRule(Form::FILLED, 'admin.mailing.send_recipients_empty');

        $form->addText('copy', 'admin.mailing.send_copy')
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, 'admin.mailing.send_copy_format');

        $form->addText('subject', 'admin.mailing.send_subject')
            ->addRule(Form::FILLED, 'admin.mailing.send_subject_empty');

        $form->addTextArea('text', 'admin.mailing.send_text')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.mailing.send_send');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    public function processForm(Form $form, \stdClass $values) {
        try {
            $this->mailService->sendMailToRoles($values['recipients'], $values['copy'], $values['subject'], $values['text']);
            $this->mailSuccess = true;
        } catch (SendException $ex) {
            $this->mailSuccess = false;
        }
    }
}
