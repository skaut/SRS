<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Repositories\FaqRepository;
use App\WebModule\Forms\FaqFormFactory;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Komponenta obsahu s FAQ.
 */
class FaqContentControl extends BaseContentControl
{
    public function __construct(
        private readonly FaqFormFactory $faqFormFactory,
        private readonly FaqRepository $faqRepository,
        private readonly RoleRepository $roleRepository,
    ) {
    }

    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/faq_content.latte');

        $template->heading   = $content->getHeading();
        $template->questions = $this->faqRepository->findPublishedOrderedByPosition();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $user                = $this->getPresenter()->user;
        $template->guestRole = $user->isInRole($this->roleRepository->findBySystemName(Role::GUEST)->getName());

        $template->render();
    }

    public function createComponentFaqForm(): Form
    {
        $form = $this->faqFormFactory->create($this->getPresenter()->getUser()->id);

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            $p = $this->getPresenter();
            $p->flashMessage('web.faq_content.add_question_successful', 'success');
            $p->redirect('this');
        };

        return $form;
    }
}
