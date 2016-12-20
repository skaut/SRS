<?php

namespace App\WebModule\Presenters;

class PagePresenter extends WebBasePresenter
{
    protected $pageId;

    public function renderDefault($pageId)
    {
        if ($pageId === null) {
            $page = $this->pageRepository->findPublishedPageBySlug('/');
            if ($page === null) {
                throw new \Nette\Application\BadRequestException($this->translator->translate('_web.common.homepage_not_found'), 404);
            }
            $this->template->bodyClass = "body-homepage";
        } else {
            $page = $this->pageRepository->find($pageId);
            $this->template->bodyClass = "body-{$page->getSlug()}";
        }

        if (!$page->isAllowedToRoles($this->user->roles)) {
            throw new \Nette\Application\BadRequestException($this->translator->translate('_web.common.page_access_denied'), 403);
        }

        $this->pageId = $page->getId();
        $this->template->pageName = $page->getName();
    }
}