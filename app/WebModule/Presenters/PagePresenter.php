<?php

declare(strict_types=1);

namespace App\WebModule\Presenters;

use App\WebModule\Components\ApplicationContentControl;
use App\WebModule\Components\BlocksContentControl;
use App\WebModule\Components\CapacitiesContentControl;
use App\WebModule\Components\ContactFormContentControl;
use App\WebModule\Components\DocumentContentControl;
use App\WebModule\Components\FaqContentControl;
use App\WebModule\Components\HtmlContentControl;
use App\WebModule\Components\IApplicationContentControlFactory;
use App\WebModule\Components\IBlocksContentControlFactory;
use App\WebModule\Components\ICapacitiesContentControlFactory;
use App\WebModule\Components\IContactFormContentControlFactory;
use App\WebModule\Components\IDocumentContentControlFactory;
use App\WebModule\Components\IFaqContentControlFactory;
use App\WebModule\Components\IHtmlContentControlFactory;
use App\WebModule\Components\IImageContentControlFactory;
use App\WebModule\Components\ILectorsContentControlFactory;
use App\WebModule\Components\ImageContentControl;
use App\WebModule\Components\INewsContentControlFactory;
use App\WebModule\Components\IOrganizerContentControlFactory;
use App\WebModule\Components\IPlaceContentControlFactory;
use App\WebModule\Components\IProgramsContentControlFactory;
use App\WebModule\Components\ISlideshowContentControlFactory;
use App\WebModule\Components\ITextContentControlFactory;
use App\WebModule\Components\IUsersContentControlFactory;
use App\WebModule\Components\LectorsContentControl;
use App\WebModule\Components\NewsContentControl;
use App\WebModule\Components\OrganizerContentControl;
use App\WebModule\Components\PlaceContentControl;
use App\WebModule\Components\ProgramsContentControl;
use App\WebModule\Components\SlideshowContentControl;
use App\WebModule\Components\TextContentControl;
use App\WebModule\Components\UsersContentControl;
use Nette\Application\BadRequestException;
use Nette\DI\Attributes\Inject;
use Throwable;

/**
 * Presenter obshlující dynamicky vytvářené stránky pomocí administrace
 */
class PagePresenter extends WebBasePresenter
{
    #[Inject]
    public IApplicationContentControlFactory $applicationContentControlFactory;

    #[Inject]
    public IBlocksContentControlFactory $blocksContentControlFactory;

    #[Inject]
    public ICapacitiesContentControlFactory $capacitiesContentControlFactory;

    #[Inject]
    public IDocumentContentControlFactory $documentContentControlFactory;

    #[Inject]
    public IFaqContentControlFactory $faqContentControlFactory;

    #[Inject]
    public IHtmlContentControlFactory $htmlContentControlFactory;

    #[Inject]
    public IImageContentControlFactory $imageContentControlFactory;

    #[Inject]
    public INewsContentControlFactory $newsContentControlFactory;

    #[Inject]
    public IPlaceContentControlFactory $placeContentControlFactory;

    #[Inject]
    public IProgramsContentControlFactory $programsContentControlFactory;

    #[Inject]
    public ITextContentControlFactory $textContentControlFactory;

    #[Inject]
    public IUsersContentControlFactory $usersContentControlFactory;

    #[Inject]
    public ILectorsContentControlFactory $lectorsContentControlFactory;

    #[Inject]
    public IOrganizerContentControlFactory $organizerContentControlFactory;

    #[Inject]
    public IContactFormContentControlFactory $contactFormContentFactory;

    #[Inject]
    public ISlideshowContentControlFactory $slideshowContentFactory;

    /**
     * @throws BadRequestException
     * @throws Throwable
     */
    public function renderDefault(?string $slug): void
    {
        if ($slug === null) {
            $page = $this->cmsService->findPublishedBySlugDto('/');
            if ($page === null) {
                $this->error($this->translator->translate('web.common.homepage_not_found'), 404);
            }

            $this->template->bodyClass = 'body-homepage';
        } else {
            $page = $this->cmsService->findPublishedBySlugDto($slug);
            if ($page === null) {
                $this->error($this->translator->translate('web.common.page_not_found'), 404);
            }

            $this->template->bodyClass = 'body-' . $slug;
        }

        if (! $page->isAllowedForRoles($this->user->roles)) {
            if (! $this->user->isLoggedIn()) {
                $this->redirect(':Auth:login', ['backlink' => $this->getHttpRequest()->getUrl()->getPath()]);
            }

            $this->error($this->translator->translate('web.common.page_access_denied'), 403);
        }

        $this->template->page     = $page;
        $this->template->pageName = $page->getName();
    }

    protected function createComponentApplicationContent(): ApplicationContentControl
    {
        return $this->applicationContentControlFactory->create();
    }

    protected function createComponentBlocksContent(): BlocksContentControl
    {
        return $this->blocksContentControlFactory->create();
    }

    protected function createComponentCapacitiesContent(): CapacitiesContentControl
    {
        return $this->capacitiesContentControlFactory->create();
    }

    protected function createComponentDocumentContent(): DocumentContentControl
    {
        return $this->documentContentControlFactory->create();
    }

    protected function createComponentFaqContent(): FaqContentControl
    {
        return $this->faqContentControlFactory->create();
    }

    protected function createComponentHtmlContent(): HtmlContentControl
    {
        return $this->htmlContentControlFactory->create();
    }

    protected function createComponentImageContent(): ImageContentControl
    {
        return $this->imageContentControlFactory->create();
    }

    protected function createComponentNewsContent(): NewsContentControl
    {
        return $this->newsContentControlFactory->create();
    }

    protected function createComponentPlaceContent(): PlaceContentControl
    {
        return $this->placeContentControlFactory->create();
    }

    protected function createComponentProgramsContent(): ProgramsContentControl
    {
        return $this->programsContentControlFactory->create();
    }

    protected function createComponentTextContent(): TextContentControl
    {
        return $this->textContentControlFactory->create();
    }

    protected function createComponentUsersContent(): UsersContentControl
    {
        return $this->usersContentControlFactory->create();
    }

    protected function createComponentLectorsContent(): LectorsContentControl
    {
        return $this->lectorsContentControlFactory->create();
    }

    protected function createComponentOrganizerContent(): OrganizerContentControl
    {
        return $this->organizerContentControlFactory->create();
    }

    protected function createComponentContactFormContent(): ContactFormContentControl
    {
        return $this->contactFormContentFactory->create();
    }

    protected function createComponentSlideshowContent(): SlideshowContentControl
    {
        return $this->slideshowContentFactory->create();
    }
}
