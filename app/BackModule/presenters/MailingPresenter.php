<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 8.5.13
 * Time: 18:13
 * To change this template use File | Settings | File Templates.
 */
namespace BackModule;

class MailingPresenter extends BasePresenter
{
    public function renderDefault()
    {

    }

    protected function createComponentMailingForm($name)
    {
        $rolesRepo = $this->context->database->getRepository('\SRS\Model\Acl\Role');
        return new \SRS\Form\Mailing\MailingForm(null, null, $rolesRepo, $this->dbsettings);
    }

}
