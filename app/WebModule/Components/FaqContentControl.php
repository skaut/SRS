<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Role;
use App\Model\Acl\RoleRepository;
use App\Model\Cms\Content\ContentDto;
use App\Model\Cms\FaqRepository;
use App\WebModule\Forms\BaseForm;
use App\WebModule\Forms\FaqFormFactory;
use Nette\Application\UI\Control;
use stdClass;

/**
 * Komponenta s FAQ.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FaqContentControl extends Control
{
    /** @var FaqFormFactory */
    private $faqFormFactory;

    /** @var FaqRepository */
    private $faqRepository;

    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(FaqFormFactory $faqFormFactory, FaqRepository $faqRepository, RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->faqFormFactory = $faqFormFactory;
        $this->faqRepository  = $faqRepository;
        $this->roleRepository = $roleRepository;
    }

    public function render(ContentDto $content) : void
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

    public function createComponentFaqForm() : BaseForm
    {
        $form = $this->faqFormFactory->create($this->getPresenter()->getUser()->id);

        $form->onSuccess[] = function (BaseForm $form, stdClass $values) : void {
            $this->getPresenter()->flashMessage('web.faq_content.add_question_successful', 'success');

            $this->getPresenter()->redirect('this');
        };

        return $form;
    }
}
