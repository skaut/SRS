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
                $page = $this->repository->findBySlug('/');
                if ($page == null) {
                    throw new \Nette\Application\BadRequestException('Stránka se slugem "/" neexistuje. Vytvořte ji v administriaci.');
                }
            }


        }
        else {
            $page = $this->repository->find($pageId);
        }
		$this->template->page = $page;
	}

}
