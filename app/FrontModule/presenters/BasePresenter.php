<?php
/**
 * Date: 15.2.13
 * Time: 11:53
 * Author: Michal Májský
 */

namespace FrontModule;

class BasePresenter extends \SRS\BasePresenter
{
    public function startup()
    {
        parent::startup();
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $path = $this->getHttpRequest()->url->path;
        $this->template->backlink = $path;
        $this->template->logo = $this->dbsettings->get('logo');
        $this->template->footer = $this->dbsettings->get('footer');
        $this->template->title = $this->dbsettings->get('seminar_name');
        if ($this->params['pageId'] !== null)
            $this->template->slug = $this->repository->IdToSlug($this->params['pageId']);
    }

    public function createComponentMenu()
    {
        $pageRepo = $this->context->database->getRepository('\SRS\Model\CMS\Page');
        $menu = new \SRS\Components\Menu($pageRepo);
        return $menu;
    }

}
