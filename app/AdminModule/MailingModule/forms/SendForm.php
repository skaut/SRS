<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\Settings\SettingsException;
use App\Model\Structure\SubeventRepository;
use App\Model\User\UserRepository;
use App\Services\ACLService;
use App\Services\MailService;
use App\Services\SubeventService;
use Nette;
use Nette\Application\UI\Form;
use Nette\Mail\SendException;
use stdClass;
use Throwable;
use Tracy\Debugger;
use Tracy\ILogger;
use Ublaboo\Mailing\Exception\MailingMailCreationException;

/**
 * Formulář pro vytvoření e-mailu.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class SendForm
{
    use Nette\SmartObject;

    /**
     * Událost po úspěšně odeslaném e-mailu.
     * @var callable
     */
    public $mailSuccess;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var MailService */
    private $mailService;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var UserRepository */
    private $userRepository;

    /** @var SubeventRepository */
    private $subeventRepository;

    /** @var ACLService */
    private $ACLService;

    /** @var SubeventService */
    private $subeventService;


    public function __construct(
        BaseForm $baseFormFactory,
        MailService $mailService,
        RoleRepository $roleRepository,
        UserRepository $userRepository,
        SubeventRepository $subeventRepository,
        ACLService $ACLService,
        SubeventService $subeventService
    ) {
        $this->baseFormFactory    = $baseFormFactory;
        $this->mailService        = $mailService;
        $this->roleRepository     = $roleRepository;
        $this->userRepository     = $userRepository;
        $this->subeventRepository = $subeventRepository;
        $this->ACLService         = $ACLService;
        $this->subeventService    = $subeventService;
    }

    /**
     * Vytvoří formulář.
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $recipientRolesMultiSelect = $form->addMultiSelect(
            'recipientRoles',
            'admin.mailing.send.recipient_roles',
            $this->ACLService->getRolesWithoutRolesOptionsWithApprovedUsersCount([Role::GUEST, Role::UNAPPROVED])
        );

        $recipientSubeventsMultiSelect = $form->addMultiSelect(
            'recipientSubevents',
            'admin.mailing.send.recipient_subevents',
            $this->subeventService->getSubeventsOptionsWithUsersCount()
        );

        $recipientUsersMultiSelect = $form->addMultiSelect(
            'recipientUsers',
            'admin.mailing.send.recipient_users',
            $this->userRepository->getUsersOptions()
        )
            ->setAttribute('data-live-search', 'true');

        $recipientRolesMultiSelect
            ->addConditionOn($recipientSubeventsMultiSelect, Form::BLANK)
            ->addConditionOn($recipientUsersMultiSelect, Form::BLANK)
            ->addRule(Form::FILLED, 'admin.mailing.send.recipients_empty');

        $recipientSubeventsMultiSelect
            ->addConditionOn($recipientRolesMultiSelect, Form::BLANK)
            ->addConditionOn($recipientUsersMultiSelect, Form::BLANK)
            ->addRule(Form::FILLED, 'admin.mailing.send.recipients_empty');

        $recipientUsersMultiSelect
            ->addConditionOn($recipientRolesMultiSelect, Form::BLANK)
            ->addConditionOn($recipientSubeventsMultiSelect, Form::BLANK)
            ->addRule(Form::FILLED, 'admin.mailing.send.recipients_empty');

        $form->addText('copy', 'admin.mailing.send.copy')
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, 'admin.mailing.send.copy_format');

        $form->addText('subject', 'admin.mailing.send.subject')
            ->addRule(Form::FILLED, 'admin.mailing.send.subject_empty');

        $form->addTextArea('text', 'admin.mailing.send.text')
            ->addRule(Form::FILLED, 'admin.mailing.send.text_empty')
            ->setAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.mailing.send.send');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws SettingsException
     * @throws Throwable
     * @throws MailingMailCreationException
     */
    public function processForm(Form $form, stdClass $values) : void
    {
        try {
            $recipientsRoles     = $this->roleRepository->findRolesByIds($values->recipientRoles);
            $recipientsSubevents = $this->subeventRepository->findSubeventsByIds($values->recipientSubevents);
            $recipientsUsers     = $this->userRepository->findUsersByIds($values->recipientUsers);

            $this->mailService->sendMail($recipientsRoles, $recipientsSubevents, $recipientsUsers, $values->copy, $values->subject, $values->text);
            $this->mailSuccess = true;
        } catch (SendException $ex) {
            Debugger::log($ex, ILogger::WARNING);
            $this->mailSuccess = false;
        }
    }
}
