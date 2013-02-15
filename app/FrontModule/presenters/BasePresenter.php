<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 15.2.13
 * Time: 11:53
 * To change this template use File | Settings | File Templates.
 */

namespace FrontModule;

class BasePresenter extends \SRS\BasePresenter
{
    public function beforeRender() {
        parent::beforeRender();
        $this->template->logo = $this->dbsettings->get('logo');
        $this->template->footer = $this->dbsettings->get('footer');
    }

}
