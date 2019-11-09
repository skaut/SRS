<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\ACL\Role;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\ContentDTO;
use App\Model\CMS\FaqRepository;
use App\WebModule\Forms\FaqForm;
use Nette\Application\UI\Control;
use Nette\Forms\Form;

/**
 * Komponenta s FAQ.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FaqContentControl extends Control
{
    /** @var FaqForm */
    private $faqFormFactory;

    /** @var FaqRepository */
    private $faqRepository;

    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(FaqForm $faqFormFactory, FaqRepository $faqRepository, RoleRepository $roleRepository)
    {
        parent::__construct();

        $this->faqFormFactory = $faqFormFactory;
        $this->faqRepository  = $faqRepository;
        $this->roleRepository = $roleRepository;
    }

    public function render(ContentDTO $content) : void
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

    public function createComponentFaqForm() : \Nette\Application\UI\Form
    {
        $form = $this->faqFormFactory->create($this->getPresenter()->getUser()->id);

        $form->onSuccess[] = function (Form $form, \stdClass $values) : void {
            $this->getPresenter()->flashMessage('web.faq_content.add_question_successful', 'success');

            $this->getPresenter()->redirect('this');
        };

        return $form;
    }
}
