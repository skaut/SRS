<?php

namespace App\AdminModule\CMSModule\Presenters;


use App\AdminModule\CMSModule\Components\IPagesGridControlFactory;
use App\AdminModule\CMSModule\Forms\IPageFormFactory;
use App\AdminModule\CMSModule\Forms\PageForm;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\ContentRepository;
use App\Model\CMS\PageRepository;
use Nette\Application\UI\Form;


class PagesPresenter extends CMSBasePresenter
{
    /**
     * @var IPagesGridControlFactory
     * @inject
     */
    public $pagesGridControlFactory;

    /**
     * @var IPageFormFactory
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


    public function renderContent($id, $area) {
        $page = $this->pagesRepository->findPageById($id);

        $this->template->page = $page;
        $this->template->id = $id;
        $this->template->area = $area;
    }

    protected function createComponentPagesGrid($name)
    {
        return $this->pagesGridControlFactory->create($name);
    }

    protected function createComponentPageForm($name)
    {
        $id = $this->getParameter('id');
        $area = $this->getParameter('area');

        $control = $this->pageFormFactory->create($id, $area);

        $control->onPageSave[] = function (PageForm $control, $submitName) {
            $this->flashMessage('admin.cms.pages_content_saved', 'success');
            switch ($submitName) {
                case 'submitAndContinue':
                case 'submitAdd':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => $control->area]);
                    break;
                case 'submitMain':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => Content::MAIN]);
                    break;
                case 'submitSidebar':
                    $this->redirect('Pages:content', ['id' => $control->id, 'area' => Content::SIDEBAR]);
                    break;
                default:
                    $this->redirect('Pages:default');
            }
        };

        $control->onPageSaveError[] = function(PageForm $control) {
            $this->flashMessage('admin.cms.pages_content_save_error', 'danger');
            $this->redirect('Pages:content', ['id' => $control->id, 'area' => $control->area]);
        };

        return $control;
    }
}