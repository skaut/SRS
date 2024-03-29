<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Mailing\Commands\CreateMail;
use App\Model\Structure\Repositories\SubeventRepository;
use App\Model\User\Repositories\UserRepository;
use App\Services\AclService;
use App\Services\CommandBus;
use App\Services\SubeventService;
use Doctrine\Common\Collections\ArrayCollection;
use Nette;
use Nette\Application\UI\Form;
use stdClass;
use Throwable;

/**
 * Formulář pro vytvoření e-mailu.
 */
class SendFormFactory
{
    use Nette\SmartObject;

    public function __construct(
        private readonly BaseFormFactory $baseFormFactory,
        private readonly CommandBus $commandBus,
        private readonly RoleRepository $roleRepository,
        private readonly UserRepository $userRepository,
        private readonly SubeventRepository $subeventRepository,
        private readonly AclService $aclService,
        private readonly SubeventService $subeventService,
    ) {
    }

    /**
     * Vytvoří formulář.
     */
    public function create(): Form
    {
        $form = $this->baseFormFactory->create();

        $recipientUsersMultiSelect = $form->addMultiSelect(
            'recipientUsers',
            'admin.mailing.send.recipient_users',
            $this->userRepository->getUsersOptions(),
        )
            ->setHtmlAttribute('data-live-search', 'true');

        $recipientRolesMultiSelect = $form->addMultiSelect(
            'recipientRoles',
            'admin.mailing.send.recipient_roles',
            $this->aclService->getRolesWithoutRolesOptionsWithApprovedUsersCount([Role::GUEST, Role::UNAPPROVED]),
        );

        $recipientSubeventsMultiSelect = $form->addMultiSelect(
            'recipientSubevents',
            'admin.mailing.send.recipient_subevents',
            $this->subeventService->getSubeventsOptionsWithUsersCount(),
        );

        $recipientUsersMultiSelect
            ->addConditionOn($recipientRolesMultiSelect, Form::BLANK)
            ->addConditionOn($recipientSubeventsMultiSelect, Form::BLANK)
            ->addRule(Form::FILLED, 'admin.mailing.send.recipients_empty');

        $recipientRolesMultiSelect
            ->addConditionOn($recipientSubeventsMultiSelect, Form::BLANK)
            ->addConditionOn($recipientUsersMultiSelect, Form::BLANK)
            ->addRule(Form::FILLED, 'admin.mailing.send.recipients_empty');

        $recipientSubeventsMultiSelect
            ->addConditionOn($recipientRolesMultiSelect, Form::BLANK)
            ->addConditionOn($recipientUsersMultiSelect, Form::BLANK)
            ->addRule(Form::FILLED, 'admin.mailing.send.recipients_empty');

        $form->addText('copy', 'admin.mailing.send.copy')
            ->addCondition(Form::FILLED)
            ->addRule(Form::EMAIL, 'admin.mailing.send.copy_format');

        $form->addText('subject', 'admin.mailing.send.subject')
            ->addRule(Form::FILLED, 'admin.mailing.send.subject_empty');

        $form->addTextArea('text', 'admin.mailing.send.text')
            ->addRule(Form::FILLED, 'admin.mailing.send.text_empty')
            ->setHtmlAttribute('class', 'tinymce-paragraph');

        $form->addSubmit('submit', 'admin.mailing.send.send');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws Throwable
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $recipientsUsers     = $this->userRepository->findUsersByIds($values->recipientUsers);
        $recipientsRoles     = $this->roleRepository->findRolesByIds($values->recipientRoles);
        $recipientsSubevents = $this->subeventRepository->findSubeventsByIds($values->recipientSubevents);
        $recipientsEmails    = null;

        if (! empty($values->copy)) {
            $recipientsEmails = new ArrayCollection([$values->copy]);
        }

        $this->commandBus->handle(new CreateMail($recipientsUsers, $recipientsRoles, $recipientsSubevents, $recipientsEmails, $values->subject, $values->text));
    }
}
