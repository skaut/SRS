<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\IPagesGridControlFactory;
use App\AdminModule\CMSModule\Forms\PageFormFactory;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\ContentRepository;
use App\Model\CMS\Page;
use App\Model\CMS\PageRepository;
use Nette\Application\UI\Form;
use Nette\Application\UI\Multiplier;

class PagesPresenter extends CMSBasePresenter
{
    /**
     * @var IPagesGridControlFactory
     * @inject
     */
    public $pagesGridControlFactory;

    /**
     * @var PageFormFactory
     * @inject
     */
    public $pageFormFactory;

    /**
     * @var PageRepository
     * @inject
     */
    public $pagesRepository;

    /**
     * @var ContentRepository
     * @inject
     */
    public $contentRepository;


    public function renderContent($id) {
        $page = $this->pagesRepository->findPageById($id);
        $area = $this->getParameter('area') ?: Content::MAIN;

        $this->template->page = $page;
        $this->template->area = $area;
        $this->template->areas = Content::$areas;
        $this->template->contents = $page->getContents($area);
    }

    public function handleDelete($cid) {
        $id = $this->getParameter('id');
        $area = $this->getParameter('area') ?: Content::MAIN;

        $this->contentRepository->removeContent($cid);
        $this->redirect('Pages:content', ['id' => $id, 'area' => $area]);
    }

    protected function createComponentPagesGrid($name)
    {
        return $this->pagesGridControlFactory->create($name);
    }

//    protected function createComponentAddContentForm($name)
//    {
//        $id = $this->getParameter('id');
//        $area = $this->getParameter('area') ?: Content::MAIN;
//
//        $form = $this->addContentFormFactory->create($id, $area);
//
//        $form->onSuccess[] = function (Form $form, \stdClass $values) {
//            $this->flashMessage('admin.cms.pages_content_added', 'success');
//            $this->redirect('Pages:content', ['id' => $values['id'], 'area' => $values['area']]);
//        };
//
//        return $form;
//    }

    protected function createComponentPageForm($name)
    {
        $id = $this->getParameter('id');
        $area = $this->getParameter('area') ?: Content::MAIN;

        $form = $this->pageFormFactory->create($id, $area);

        $form->onSuccess[] = function (Form $form, \stdClass $values) {
            $this->flashMessage('admin.cms.pages_content_saved', 'success');

            if ($form['submitAndContinue']->isSubmittedBy() || $form['submitAdd']->isSubmittedBy())
                $this->redirect('Pages:content', ['id' => $values['id'], 'area' => $values['area']]);
            elseif ($form['submitMain']->isSubmittedBy())
                $this->redirect('Pages:content', ['id' => $values['id'], 'area' => Content::MAIN]);
            elseif ($form['submitSidebar']->isSubmittedBy())
                $this->redirect('Pages:content', ['id' => $values['id'], 'area' => Content::SIDEBAR]);
            else
                $this->redirect('Pages:default');
        };

        return $form;
    }
}