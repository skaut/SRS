<?php

declare(strict_types=1);

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\MailingModule\Components\IMailTemplatesGridControlFactory;
use App\AdminModule\MailingModule\Components\MailTemplatesGridControl;
use App\AdminModule\MailingModule\Forms\EditTemplateForm;
use App\Model\Mailing\TemplateRepository;
use Nette\Forms\Form;

/**
 * Presenter obsluhující nastavení šablon automatických e-mailů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TemplatesPresenter extends MailingBasePresenter
{
    /**
     * @var TemplateRepository
     * @inject
     */
    public $templateRepository;

    /**
     * @var IMailTemplatesGridControlFactory
     * @inject
     */
    public $mailTemplatesGridControlFactory;

    /**
     * @var EditTemplateForm
     * @inject
     */
    public $editTemplateFormFactory;


    public function renderEdit(int $id) : void
    {
        $template = $this->templateRepository->findById($id);

        $this->template->editedTemplate = $template;
        $this->template->translator     = $this->translator;
    }

    protected function createComponentMailTemplatesGrid() : MailTemplatesGridControl
    {
        return $this->mailTemplatesGridControlFactory->create();
    }

    protected function createComponentEditTemplateForm() : Form
    {
        $form = $this->editTemplateFormFactory->create((int) $this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) : void {
            if ($form['cancel']->isSubmittedBy()) {
                $this->redirect('Templates:default');
            }

            $this->flashMessage('admin.mailing.templates.saved', 'success');

            $this->redirect('Templates:default');
        };

        return $form;
    }
}
