<?php

namespace App\WebModule\Presenters;

use App\WebModule\Components\ApplicationContentControl;
use App\WebModule\Components\BlocksContentControl;
use App\WebModule\Components\CapacitiesContentControl;
use App\WebModule\Components\DocumentContentControl;
use App\WebModule\Components\FaqContentControl;
use App\WebModule\Components\HtmlContentControl;
use App\WebModule\Components\IApplicationContentControlFactory;
use App\WebModule\Components\IBlocksContentControlFactory;
use App\WebModule\Components\ICapacitiesContentControlFactory;
use App\WebModule\Components\IDocumentContentControlFactory;
use App\WebModule\Components\IFaqContentControlFactory;
use App\WebModule\Components\IHtmlContentControlFactory;
use App\WebModule\Components\IImageContentControlFactory;
use App\WebModule\Components\ImageContentControl;
use App\WebModule\Components\INewsContentControlFactory;
use App\WebModule\Components\IProgramsContentControlFactory;
use App\WebModule\Components\ITextContentControlFactory;
use App\WebModule\Components\IUsersContentControlFactory;
use App\WebModule\Components\NewsContentControl;
use App\WebModule\Components\ProgramsContentControl;
use App\WebModule\Components\TextContentControl;
use App\WebModule\Components\UsersContentControl;
use Nette\Application\BadRequestException;

class PagePresenter extends WebBasePresenter
{
    /**
     * @var IApplicationContentControlFactory
     * @inject
     */
    public $applicationContentControlFactory;

    /**
     * @var IBlocksContentControlFactory
     * @inject
     */
    public $blocksContentControlFactory;

    /**
     * @var ICapacitiesContentControlFactory
     * @inject
     */
    public $capacitiesContentControlFactory;

    /**
     * @var IDocumentContentControlFactory
     * @inject
     */
    public $documentContentControlFactory;

    /**
     * @var IFaqContentControlFactory
     * @inject
     */
    public $faqContentControlFactory;

    /**
     * @var IHtmlContentControlFactory
     * @inject
     */
    public $htmlContentControlFactory;

    /**
     * @var IImageContentControlFactory
     * @inject
     */
    public $imageContentControlFactory;

    /**
     * @var INewsContentControlFactory
     * @inject
     */
    public $newsContentControlFactory;

    /**
     * @var IProgramsContentControlFactory
     * @inject
     */
    public $programsContentControlFactory;

    /**
     * @var ITextContentControlFactory
     * @inject
     */
    public $textContentControlFactory;

    /**
     * @var IUsersContentControlFactory
     * @inject
     */
    public $usersContentControlFactory;


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

        $this->template->page = $page;
        $this->template->pageName = $page->getName();
        $this->template->sidebarVisible = $page->hasContents('sidebar');
    }

    protected function createComponentApplicationContent($name)
    {
        return $this->applicationContentControlFactory->create($name);
    }

    protected function createComponentBlocksContent($name)
    {
        return $this->blocksContentControlFactory->create($name);
    }

    protected function createComponentCapacitiesContent($name)
    {
        return $this->capacitiesContentControlFactory->create($name);
    }

    protected function createComponentDocumentContent($name)
    {
        return $this->documentContentControlFactory->create($name);
    }

    protected function createComponentFaqContent($name)
    {
        return $this->faqContentControlFactory->create($name);
    }

    protected function createComponentHtmlContent($name)
    {
        return $this->htmlContentControlFactory->create($name);
    }

    protected function createComponentImageContent($name)
    {
        return $this->imageContentControlFactory->create($name);
    }

    protected function createComponentNewsContent($name)
    {
        return $this->newsContentControlFactory->create($name);
    }

    protected function createComponentProgramsContent($name)
    {
        return $this->programsContentControlFactory->create($name);
    }

    protected function createComponentTextContent($name)
    {
        return $this->textContentControlFactory->create($name);
    }

    protected function createComponentUsersContent($name)
    {
        return $this->usersContentControlFactory->create($name);
    }
}