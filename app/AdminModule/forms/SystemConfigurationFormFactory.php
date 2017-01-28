<?php

namespace App\AdminModule\Forms;

class SystemConfigurationFormFactory
{
    /**
     * @var BaseFormFactory
     */
    private $baseFormFactory;

    public function __construct(BaseFormFactory $baseFormFactory)
    {
        $this->baseFormFactory = $baseFormFactory;
    }

    public function create()
    {
        $form = $this->baseFormFactory->create();

        $renderer = $form->getRenderer();
        $renderer->wrappers['control']['container'] = 'div class="col-sm-7 col-xs-7"';
        $renderer->wrappers['label']['container'] = 'div class="col-sm-5 col-xs-5 control-label"';

        $form->addText('footer', 'admin.configuration.footer');
        $form->addSelect('redirectAfterLogin', 'admin.configuration.redirect_after_login'); //TODO seznam stranek
        $form->addCheckbox('displayUsersRoles', 'admin.configuration.display_users_roles');

        $form->addSubmit('submit', 'admin.common.save');

        return $form;
    }
}
