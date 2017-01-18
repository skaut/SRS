<?php

namespace App\WebModule\Presenters;

use App\WebModule\Components\ApplicationContentControl;
use App\WebModule\Components\BlocksContentControl;
use App\WebModule\Components\CapacitiesContentControl;
use App\WebModule\Components\DocumentContentControl;
use App\WebModule\Components\FaqContentControl;
use App\WebModule\Components\HtmlContentControl;
use App\WebModule\Components\ImageContentControl;
use App\WebModule\Components\NewsContentControl;
use App\WebModule\Components\ProgramsContentControl;
use App\WebModule\Components\TextContentControl;
use App\WebModule\Components\UsersContentControl;

class PagePresenter extends WebBasePresenter
{
    protected $pageId;

    public function renderDefault($slug)
    {
        if ($slug === null) {
            $page = $this->pageRepository->findPublishedPageBySlug('/');
            if ($page === null) {
                throw new \Nette\Application\BadRequestException($this->translator->translate('_web.common.homepage_not_found'), 404);
            }
            $this->template->bodyClass = "body-homepage";
        } else {
            $page = $this->pageRepository->findPageBySlug($slug);
            $this->template->bodyClass = "body-{$page->getSlug()}";
        }

        if (!$page->isAllowedForRoles($this->user->roles)) {
            throw new \Nette\Application\BadRequestException($this->translator->translate('_web.common.page_access_denied'), 403);
        }

        $this->pageId = $page->getId();

        $this->template->page = $page;
        $this->template->pageName = $page->getName();
        $this->template->sidebarVisible = $page->hasContents('sidebar');
    }

    public function createComponentApplicationContent($content)
    {
        return new ApplicationContentControl;
    }

    public function createComponentBlocksContent($content)
    {
        return new BlocksContentControl;
    }

    public function createComponentCapacitiesContent($content)
    {
        return new CapacitiesContentControl;
    }

    public function createComponentDocumentContent($content)
    {
        return new DocumentContentControl;
    }

    public function createComponentFaqContent($content)
    {
        return new FaqContentControl;
    }

    public function createComponentHtmlContent($content)
    {
        return new HtmlContentControl;
    }

    public function createComponentImageContent($content)
    {
        return new ImageContentControl;
    }

    public function createComponentNewsContent($content)
    {
        return new NewsContentControl;
    }

    public function createComponentProgramsContent($content)
    {
        return new ProgramsContentControl;
    }

    public function createComponentTextContent($content)
    {
        return new TextContentControl;
    }

    public function createComponentUsersContent($content)
    {
        return new UsersContentControl;
    }
}