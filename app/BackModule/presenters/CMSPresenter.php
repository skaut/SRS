<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Michal
 * Date: 30.10.12
 * Time: 21:16
 * To change this template use File | Settings | File Templates.
 */
namespace BackModule;
class CMSPresenter extends BasePresenter
{
    protected function createComponentUserGrid()
    {
        return new \SRS\Components\UserGrid($this->context->database);
    }

    public function startup() {
        parent::startup();

    }

    public function renderPages() {
        //$pages = $this->context->database->getRepository('\SRS\Model\CMS\Page')->findAll();
        $query = $this->context->database->createQuery('SELECT p FROM \SRS\Model\CMS\Page p ORDER BY p.position');
        $pages = $query->getResult();
        $this->template->pages = $pages;
    }

    public function handleSortPages() {
        $pagesOrder = $this->getParameter('pages');
        $position = 0;
        foreach ($pagesOrder as $pageId) {
            $page = $this->context->database->getRepository('\SRS\Model\CMS\Page')->find($pageId);
            $page->position = $position;
            $this->context->database->persist($page);
            $position++;
        }
        $this->context->database->flush();
        $this->invalidateControl('pagelist');


    }

    public function renderPage($pageId) {

    }


    protected function createComponentNewPageForm($name)
    {
        $form = new \SRS\Form\CMS\NewPageForm();
        return $form;
    }


}
