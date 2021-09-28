<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Repositories\FaqRepository;
use App\WebModule\Forms\FaqFormFactory;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Komponenta s FAQ.
 */
class FaqContentControl extends BaseContentControl
{
    /** @ORM\Column(type="string") */
    private FaqFormFactory $faqFormFactory;

    private FaqRepository $faqRepository;

    private RoleRepository $roleRepository;

    public function __construct(FaqFormFactory $faqFormFactory, FaqRepository $faqRepository, RoleRepository $roleRepository)
    {
        $this->faqFormFactory = $faqFormFactory;
        $this->faqRepository  = $faqRepository;
        $this->roleRepository = $roleRepository;
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
            $this->getPresenter()->flashMessage('web.faq_content.add_question_successful', 'success');

            $this->getPresenter()->redirect('this');
        };

        return $form;
    }
}
