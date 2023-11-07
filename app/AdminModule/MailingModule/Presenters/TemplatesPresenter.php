<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\MailingModule\Components\IMailTemplatesGridControlFactory;
use App\AdminModule\MailingModule\Components\MailTemplatesGridControl;
use App\AdminModule\MailingModule\Forms\EditTemplateFormFactory;
use App\Model\Mailing\Repositories\TemplateRepository;
use Nette\Application\UI\Form;
use Nette\DI\Attributes\Inject;
use stdClass;

/**
 * Presenter obsluhující nastavení šablon automatických e-mailů.
 */
class TemplatesPresenter extends MailingBasePresenter
{
    #[Inject]
    public TemplateRepository $templateRepository;

    #[Inject]
    public IMailTemplatesGridControlFactory $mailTemplatesGridControlFactory;

    #[Inject]
    public EditTemplateFormFactory $editTemplateFormFactory;

    public function renderEdit(int $id): void
    {
        $template = $this->templateRepository->findById($id);

        $this->template->editedTemplate = $template;
        $this->template->translator     = $this->translator;
    }

    protected function createComponentMailTemplatesGrid(): MailTemplatesGridControl
    {
        return $this->mailTemplatesGridControlFactory->create();
    }

    protected function createComponentEditTemplateForm(): Form
    {
        $form = $this->editTemplateFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, stdClass $values): void {
            if ($form->isSubmitted() == $form['cancel']) {
                $this->redirect('Templates:default');
            }

            $this->flashMessage('admin.mailing.templates.saved', 'success');

            $this->redirect('Templates:default');
        };

        return $form;
    }
}
