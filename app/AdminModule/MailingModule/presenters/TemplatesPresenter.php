<?php

namespace App\AdminModule\MailingModule\Presenters;

use App\AdminModule\MailingModule\Components\IMailTemplatesGridControlFactory;
use App\Model\Mailing\TemplateRepository;


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
     * @param $id
     */
    public function renderEdit($id)
    {
        $template = $this->templateRepository->findById($id);

        $this->template->editedTemplate = $template;
    }

    protected function createComponentMailTemplatesGrid()
    {
        return $this->mailTemplatesGridControlFactory->create();
    }
}
