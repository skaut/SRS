<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\CMS\PageRepository;
use Nette;
use Nette\Application\UI\Form;

class SettingsForm extends Nette\Object
{
    /**
     * @var BaseForm
     */
    private $baseFormFactory;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    public function __construct(BaseForm $baseFormFactory, PageRepository $pageRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository = $pageRepository;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addUpload('logo', 'admin.cms.settings_new_logo')
            ->setAttribute('accept', 'image/*')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE, 'admin.cms.settings_new_logo_format');

        $form->addText('footer', 'admin.cms.settings_footer');

        $pagesChoices = $this->preparePagesChoices();

        $form->addSelect('redirectAfterLogin', 'admin.cms.settings_redirect_after_login', $pagesChoices)
            ->addRule(Form::FILLED, 'admin.cms.settings_redirect_after_login_empty');

        $form->addCheckbox('displayUsersRoles', 'admin.cms.settings_display_users_roles');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }

    private function preparePagesChoices() {
        $choices = [];
        foreach ($this->pageRepository->findPublishedPagesOrderedBySlug() as $page)
            $choices[$page->getSlug()] = $page->getName();
        return $choices;
    }
}
