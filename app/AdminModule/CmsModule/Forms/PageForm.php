<?php

declare(strict_types=1);

namespace App\AdminModule\CmsModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\Acl\Repositories\RoleRepository;
use App\Model\Cms\CapacitiesContent;
use App\Model\Cms\Content;
use App\Model\Cms\DocumentContent;
use App\Model\Cms\Exceptions\PageException;
use App\Model\Cms\ImageContent;
use App\Model\Cms\Page;
use App\Model\Cms\Repositories\PageRepository;
use App\Model\Cms\Repositories\TagRepository;
use App\Model\Cms\SlideshowContent;
use App\Model\Cms\UsersContent;
use App\Services\AclService;
use App\Services\CmsService;
use App\Services\FilesService;
use Doctrine\ORM\OptimisticLockException;
use Nette\Application\UI;
use Nette\Application\UI\Form;
use stdClass;

use function str_replace;
use function ucwords;

/**
 * Komponenta s formulářem pro úpravu obsahu stránky.
 */
class PageForm extends UI\Control
{
    /**
     * Upravovaná stránka.
     */
    private Page|null $page;

    /**
     * Událost při uložení formuláře.
     *
     * @var callable[]
     */
    public array $onPageSave = [];

    /**
     * Událost při chybě ukládání stránky.
     *
     * @var callable[]
     */
    public array $onPageSaveError = [];

    /**
     * @param int    $id   Id upravované stránky.
     * @param string $area Upravovaná oblast.
     */
    public function __construct(
        public int $id,
        public string $area,
        private readonly BaseFormFactory $baseFormFactory,
        private readonly PageRepository $pageRepository,
        private readonly AclService $aclService,
        private readonly CmsService $cmsService,
        private readonly RoleRepository $roleRepository,
        private readonly TagRepository $tagRepository,
        private readonly FilesService $filesService,
    ) {
        $this->page = $this->pageRepository->findById($id);
    }

    /**
     * Vykreslí komponentu.
     *
     * @throws PageException
     */
    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/templates/page_form.latte');

        $this->template->area     = $this->area;
        $this->template->contents = $this->page->getContents($this->area);

        $this->template->render();
    }

    /**
     * Vykreslí skripty komponenty.
     */
    public function renderScripts(): void
    {
        $this->template->setFile(__DIR__ . '/templates/page_form_scripts.latte');
        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     *
     * @throws PageException
     */
    public function createComponentForm(): Form
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id')->setDefaultValue($this->page->getId());
        $form->addHidden('area')->setDefaultValue($this->area);
        $form->addSelect('type', 'admin.cms.pages.content.form.type', $this->prepareContentTypesOptions());

        foreach ($this->page->getContents($this->area) as $content) {
            switch ($content::class) {
                case CapacitiesContent::class:
                    $content->injectRoleRepository($this->roleRepository);
                    $content->injectAclService($this->aclService);
                    break;
                case DocumentContent::class:
                    $content->injectTagRepository($this->tagRepository);
                    break;
                case ImageContent::class:
                    $content->injectFilesService($this->filesService);
                    break;
                case SlideshowContent::class:
                    $content->injectFilesService($this->filesService);
                    break;
                case UsersContent::class:
                    $content->injectRoleRepository($this->roleRepository);
                    $content->injectAclService($this->aclService);
                    break;
            }

            $form = $content->addContentForm($form);
        }

        $form->addSubmit('submit', 'admin.common.save');
        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');
        $form->addSubmit('submitAdd', 'admin.common.add');
        $form->addSubmit('submitMain', 'common.area.main')
            ->setHtmlAttribute('class', 'nav-link');
        $form->addSubmit('submitSidebar', 'common.area.sidebar')
            ->setHtmlAttribute('class', 'nav-link');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        $form->onError[] = function (): void {
            $this->onPageSaveError($this);
        };

        return $form;
    }

    /**
     * Zpracuje formulář.
     *
     * @throws PageException
     * @throws OptimisticLockException
     */
    public function processForm(Form $form, stdClass $values): void
    {
        $page = $this->pageRepository->findById((int) $values->id);

        $area = $values->area;
        $type = $values->type;

        foreach ($page->getContents($area) as $content) {
            $inputName     = $content->getContentFormName();
            $formContainer = $values->$inputName;
            if ($formContainer['delete']) {
                $this->cmsService->removeContent($content);
            } else {
                $content->contentFormSucceeded($form, $values);
                $this->cmsService->saveContent($content);
            }
        }

        if ($form->isSubmitted() == $form['submitAdd']) {
            $contentClass = '\\App\\Model\\Cms\\' . str_replace('_', '', ucwords($type, '_')) . 'Content';
            $content      = new $contentClass($page, $area);
            $content->setHeading($form->getTranslator()->translate('common.content.default_heading.' . $type));
            $this->cmsService->saveContent($content);
        }

        if ($form->isSubmitted() == $form['submitAdd']) {
            $submitName = 'submitAdd';
        } elseif ($form->isSubmitted() == $form['submitMain']) {
            $submitName = 'submitMain';
        } elseif ($form->isSubmitted() == $form['submitSidebar']) {
            $submitName = 'submitSidebar';
        } elseif ($form->isSubmitted() == $form['submitAndContinue']) {
            $submitName = 'submitAndContinue';
        } else {
            $submitName = 'submit';
        }

        $this->onPageSave($this, $submitName);
    }

    /**
     * Připraví možnosti obsahů stránky pro select.
     *
     * @return string[]
     */
    private function prepareContentTypesOptions(): array
    {
        $options = [];
        foreach (Content::$types as $type) {
            $options[$type] = 'common.content.name.' . $type;
        }

        return $options;
    }
}
