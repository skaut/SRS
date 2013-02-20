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
    public function startup() {
        parent::startup();
    }

    public function beforeRender() {
        parent::beforeRender();
        $path = $this->getHttpRequest()->url->path;
        $this->template->backlink = $path;
        $this->template->logo = $this->dbsettings->get('logo');
        $this->template->footer = $this->dbsettings->get('footer');
    }

    public function createComponentMenu() {
        $pageRepo = $this->context->database->getRepository('\SRS\Model\CMS\Page');
        $menu = new \SRS\Components\Menu($pageRepo);
        return $menu;
    }

}
