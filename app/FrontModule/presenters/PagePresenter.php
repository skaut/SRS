<?php

/**
 * Homepage presenter.
 */
namespace FrontModule;

class PagePresenter extends \SRS\BasePresenter
{
    protected $repository;

    public function startup() {
        parent::startup();
        $this->repository = $this->context->database->getRepository('\SRS\Model\CMS\Page');
    }

	public function renderDefault($pageId)
	{
        if ($pageId == null) {
            $httpRequest = $this->context->getService('httpRequest');
            if ($httpRequest->url->path == '/') {
                $page = $this->repository->findBy(array('slug' => '/', 'public' => true));
                if ($page == null) {
                    throw new \Nette\Application\BadRequestException('Stránka se slugem "/" neexistuje nebo není zveřejněná. Vytvořte ji v administriaci.');
                }
            }


        }
        else {
            $page = $this->repository->find($pageId);
        }
		$this->template->page = $page;
	}

    public function createComponentMenu() {
        $pageRepo = $this->context->database->getRepository('\SRS\Model\CMS\Page');
        $menu = new \SRS\Components\Menu($pageRepo);
        return $menu;
    }

}
