<?php

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\MailingModule\Components\IMailTemplatesGridControlFactory;
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


    /**
     * @param $id
     */
    public function renderEdit($id)
    {
        $template = $this->templateRepository->findById($id);

        $this->template->editedTemplate = $template;
        $this->template->translator = $this->translator;
    }

    /**
     * @return \App\AdminModule\MailingModule\Components\MailTemplatesGridControl
     */
    protected function createComponentMailTemplatesGrid()
    {
        return $this->mailTemplatesGridControlFactory->create();
    }

    /**
     * @return \Nette\Application\UI\Form
     */
    protected function createComponentEditTemplateForm()
    {
        $form = $this->editTemplateFormFactory->create($this->getParameter('id'));

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            if ($form['cancel']->isSubmittedBy())
                $this->redirect('Templates:default');

            $this->flashMessage('admin.mailing.templates_saved', 'success');

            $this->redirect('Templates:default');
        };

        return $form;
    }
}
