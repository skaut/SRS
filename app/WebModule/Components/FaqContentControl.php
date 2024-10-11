<?php

declare(strict_types=1);

namespace App\WebModule\Components;

use App\Model\Acl\Role;
use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Repositories\FaqRepository;
use App\Services\AclService;
use App\WebModule\Forms\FaqFormFactory;
use App\WebModule\Presenters\WebBasePresenter;
use Nette\Application\UI\Form;
use stdClass;

use function assert;

/**
 * Komponenta obsahu s FAQ.
 */
class FaqContentControl extends BaseContentControl
{
    public function __construct(
        private readonly FaqFormFactory $faqFormFactory,
        private readonly FaqRepository $faqRepository,
        private readonly AclService $aclService,
    ) {
    }

    public function render(ContentDto $content): void
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/templates/faq_content.latte');

        $template->heading   = $content->getHeading();
        $template->questions = $this->faqRepository->findPublishedOrderedByPosition();

        $template->backlink = $this->getPresenter()->getHttpRequest()->getUrl()->getPath();

        $template->guestRole = $this->getPresenter()->getUser()->isInRole($this->aclService->findRoleNameBySystemName(Role::GUEST));

        $template->render();
    }

    public function createComponentFaqForm(): Form
    {
        $p = $this->getPresenter();
        assert($p instanceof WebBasePresenter);

        $form = $this->faqFormFactory->create($p->getDbUser());

        $form->onSuccess[] = static function (Form $form, stdClass $values) use ($p): void {
            $p->flashMessage('web.faq_content.add_question_successful', 'success');
            $p->redirect('this');
        };

        return $form;
    }
}
