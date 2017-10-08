<?php

namespace App\WebModule\Presenters;

use App\WebModule\Components\IApplicationContentControlFactory;
use App\WebModule\Components\IBlocksContentControlFactory;
use App\WebModule\Components\ICapacitiesContentControlFactory;
use App\WebModule\Components\IDocumentContentControlFactory;
use App\WebModule\Components\IFaqContentControlFactory;
use App\WebModule\Components\IHtmlContentControlFactory;
use App\WebModule\Components\IImageContentControlFactory;
use App\WebModule\Components\ILectorsContentControlFactory;
use App\WebModule\Components\INewsContentControlFactory;
use App\WebModule\Components\IOrganizerContentControlFactory;
use App\WebModule\Components\IPlaceContentControlFactory;
use App\WebModule\Components\IProgramsContentControlFactory;
use App\WebModule\Components\ITextContentControlFactory;
use App\WebModule\Components\IUsersContentControlFactory;
use Nette\Application\BadRequestException;


/**
 * Presenter obshlující dynamicky vytvářené stránky pomocí administrace.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
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
     * @var IPlaceContentControlFactory
     * @inject
     */
    public $placeContentControlFactory;

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

    /**
     * @var ILectorsContentControlFactory
     * @inject
     */
    public $lectorsContentControlFactory;

    /**
     * @var IOrganizerContentControlFactory
     * @inject
     */
    public $organizerContentControlFactory;


    public function renderDefault($slug)
    {
        if ($slug === NULL) {
            $page = $this->pageRepository->findPublishedBySlug('/');
            if ($page === NULL) {
                throw new BadRequestException($this->translator->translate('web.common.homepage_not_found'), 404);
            }
            $this->template->bodyClass = "body-homepage";
        } else {
            $page = $this->pageRepository->findBySlug($slug);
            $this->template->bodyClass = "body-{$page->getSlug()}";
        }

        if (!$page->isAllowedForRoles($this->user->roles)) {
            throw new BadRequestException($this->translator->translate('web.common.page_access_denied'), 403);
        }

        $this->template->page = $page;
        $this->template->pageName = $page->getName();
        $this->template->sidebarVisible = $page->hasContents('sidebar');
    }

    protected function createComponentApplicationContent()
    {
        return $this->applicationContentControlFactory->create();
    }

    protected function createComponentBlocksContent()
    {
        return $this->blocksContentControlFactory->create();
    }

    protected function createComponentCapacitiesContent()
    {
        return $this->capacitiesContentControlFactory->create();
    }

    protected function createComponentDocumentContent()
    {
        return $this->documentContentControlFactory->create();
    }

    protected function createComponentFaqContent()
    {
        return $this->faqContentControlFactory->create();
    }

    protected function createComponentHtmlContent()
    {
        return $this->htmlContentControlFactory->create();
    }

    protected function createComponentImageContent()
    {
        return $this->imageContentControlFactory->create();
    }

    protected function createComponentNewsContent()
    {
        return $this->newsContentControlFactory->create();
    }

    protected function createComponentPlaceContent()
    {
        return $this->placeContentControlFactory->create();
    }

    protected function createComponentProgramsContent()
    {
        return $this->programsContentControlFactory->create();
    }

    protected function createComponentTextContent()
    {
        return $this->textContentControlFactory->create();
    }

    protected function createComponentUsersContent()
    {
        return $this->usersContentControlFactory->create();
    }

    protected function createComponentLectorsContent()
    {
        return $this->lectorsContentControlFactory->create();
    }

    protected function createComponentOrganizerContent()
    {
        return $this->organizerContentControlFactory->create();
    }
}
