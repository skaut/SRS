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
use Nette\Application\BadRequestException;

class PagePresenter extends WebBasePresenter
{
    protected $pageId;

    public function renderDefault($slug)
    {
        if ($slug === null) {
            $page = $this->pageRepository->findPublishedPageBySlug('/');
            if ($page === null) {
                throw new BadRequestException($this->translator->translate('_web.common.homepage_not_found'), 404);
            }
            $this->template->bodyClass = "body-homepage";
        } else {
            $page = $this->pageRepository->findPageBySlug($slug);
            $this->template->bodyClass = "body-{$page->getSlug()}";
        }

        if (!$page->isAllowedForRoles($this->user->roles)) {
            throw new BadRequestException($this->translator->translate('_web.common.page_access_denied'), 403);
        }

        $this->pageId = $page->getId();

        $this->template->page = $page;
        $this->template->pageName = $page->getName();
        $this->template->sidebarVisible = $page->hasContents('sidebar');
    }

    protected function createComponentApplicationContent($content)
    {
        return new ApplicationContentControl;
    }

    protected function createComponentBlocksContent($content)
    {
        return new BlocksContentControl;
    }

    protected function createComponentCapacitiesContent($content)
    {
        return new CapacitiesContentControl;
    }

    protected function createComponentDocumentContent($content)
    {
        return new DocumentContentControl;
    }

    protected function createComponentFaqContent($content)
    {
        return new FaqContentControl;
    }

    protected function createComponentHtmlContent($content)
    {
        return new HtmlContentControl;
    }

    protected function createComponentImageContent($content)
    {
        return new ImageContentControl;
    }

    protected function createComponentNewsContent($content)
    {
        return new NewsContentControl;
    }

    protected function createComponentProgramsContent($content)
    {
        return new ProgramsContentControl;
    }

    protected function createComponentTextContent($content)
    {
        return new TextContentControl;
    }

    protected function createComponentUsersContent($content)
    {
        return new UsersContentControl;
    }
}