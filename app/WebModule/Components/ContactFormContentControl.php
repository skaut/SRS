<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\Settings\Queries\SettingBoolValueQuery;
use App\Model\Settings\Settings;
use App\Services\QueryBus;
use App\WebModule\Forms\ContactForm;
use App\WebModule\Forms\IContactFormFactory;
use Throwable;

/**
 * Komponenta s kontaktním formulářem.
 */
class ContactFormContentControl extends BaseContentControl
{
    public function __construct(
        private QueryBus $queryBus,
        private IContactFormFactory $contactFormFactory,
        private RoleRepository $roleRepository
    ) {
    }

    /**
     * @throws Throwable
     */
    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/contact_form_content.latte');

        $template->heading = $content->getHeading();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $user                    = $this->getPresenter()->user;
        $template->guestRole     = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());
        $template->guestsAllowed = $this->queryBus->handle(new SettingBoolValueQuery(Settings::CONTACT_FORM_GUESTS_ALLOWED));

        $template->render();
    }

    public function createComponentContactForm(): ContactForm
    {
        $form = $this->contactFormFactory->create();

        $form->onSave[] = function (): void {
            $p = $this->getPresenter();
            $p->flashMessage('web.contact_form_content.send_message_successful', 'success');
            $p->redirect('this');
        };

        return $form;
    }
}
