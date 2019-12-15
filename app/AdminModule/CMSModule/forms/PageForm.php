<?php

declare(strict_types=1);

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\AdminModule\Forms\BaseFormFactory;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\CapacitiesContent;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\DocumentContent;
use App\Model\CMS\Content\ImageContent;
use App\Model\CMS\Content\UsersContent;
use App\Model\CMS\Document\TagRepository;
use App\Model\CMS\Page;
use App\Model\CMS\PageRepository;
use App\Model\Page\PageException;
use App\Services\ACLService;
use App\Services\CMSService;
use App\Services\FilesService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Nette\Application\UI;
use stdClass;
use function get_class;
use function ucfirst;

/**
 * Komponenta s formulářem pro úpravu obsahu stránky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class PageForm extends UI\Control
{
    /**
     * Id upravované stránky.
     * @var int
     */
    public $id;

    /**
     * Upravovaná stránka.
     * @var Page
     */
    private $page;

    /**
     * Upravovaná oblast.
     * @var string
     */
    public $area;

    /**
     * Událost při uložení formuláře.
     * @var callable
     */
    public $onPageSave;

    /**
     * Událost při chybě ukládání stránky.
     * @var callable
     */
    public $onPageSaveError;

    /** @var BaseFormFactory */
    private $baseFormFactory;

    /** @var PageRepository */
    private $pageRepository;

    /** @var ACLService */
    private $ACLService;

    /** @var CMSService */
    private $CMSService;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var TagRepository */
    private $tagRepository;

    /** @var FilesService */
    private $filesService;


    public function __construct(
        int $id,
        string $area,
        BaseFormFactory $baseFormFactory,
        PageRepository $pageRepository,
        ACLService $ACLService,
        CMSService $CMSService,
        RoleRepository $roleRepository,
        TagRepository $tagRepository,
        FilesService $filesService
    ) {
        parent::__construct();

        $this->id   = $id;
        $this->area = $area;

        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository  = $pageRepository;
        $this->ACLService      = $ACLService;
        $this->CMSService      = $CMSService;
        $this->roleRepository  = $roleRepository;
        $this->tagRepository   = $tagRepository;
        $this->filesService    = $filesService;

        $this->page = $this->pageRepository->findById($id);
    }

    /**
     * Vykreslí komponentu.
     * @throws PageException
     */
    public function render() : void
    {
        $this->template->setFile(__DIR__ . '/templates/page_form.latte');

        $this->template->area     = $this->area;
        $this->template->contents = $this->page->getContents($this->area);

        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     * @throws PageException
     */
    public function createComponentForm() : BaseForm
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id')->setDefaultValue($this->page->getId());
        $form->addHidden('area')->setDefaultValue($this->area);
        $form->addSelect('type', 'admin.cms.pages_content_type', $this->prepareContentTypesOptions());

        foreach ($this->page->getContents($this->area) as $content) {
            switch (get_class($content)) {
                case CapacitiesContent::class:
                    $content->injectRoleRepository($this->roleRepository);
                    $content->injectACLService($this->ACLService);
                    break;
                case DocumentContent::class:
                    $content->injectTagRepository($this->tagRepository);
                    break;
                case ImageContent::class:
                    $content->injectFilesService($this->filesService);
                    break;
                case UsersContent::class:
                    $content->injectRoleRepository($this->roleRepository);
                    $content->injectACLService($this->ACLService);
                    break;
            }
            $form = $content->addContentForm($form);
        }

        $form->addSubmit('submit', 'admin.common.save');
        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');
        $form->addSubmit('submitAdd', 'admin.common.add');
        $form->addSubmit('submitMain', 'common.area.main')
            ->setAttribute('class', 'btn-link');
        $form->addSubmit('submitSidebar', 'common.area.sidebar')
            ->setAttribute('class', 'btn-link');

        $form->getElementPrototype()->onsubmit('tinyMCE.triggerSave()');
        $form->onSuccess[] = [$this, 'processForm'];

        $form->onError[] = function () : void {
            $this->onPageSaveError($this);
        };

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws PageException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function processForm(BaseForm $form, stdClass $values) : void
    {
        $page = $this->pageRepository->findById((int) $values->id);

        $area = $values->area;
        $type = $values->type;

        foreach ($page->getContents($area) as $content) {
            $inputName = $content->getContentFormName();
            $formContainer = $values->$inputName;
            if ($formContainer['delete']) {
                $this->CMSService->removeContent($content);
            } else {
                $content->contentFormSucceeded($form, $values);
                $this->CMSService->saveContent($content);
            }
        }

        if ($form->isSubmitted() === $form['submitAdd']) {
            $contentClass = '\\App\\Model\\CMS\\Content\\' . ucfirst($type) . 'Content';
            $content      = new $contentClass($page, $area);
            $content->setHeading($form->getTranslator()->translate('common.content.default_heading.' . $type));
            $this->CMSService->saveContent($content);
        }

        if ($form->isSubmitted() === $form['submitAdd']) {
            $submitName = 'submitAdd';
        } elseif ($form->isSubmitted() === $form['submitMain']) {
            $submitName = 'submitMain';
        } elseif ($form->isSubmitted() === $form['submitSidebar']) {
            $submitName = 'submitSidebar';
        } elseif ($form->isSubmitted() === $form['submitAndContinue']) {
            $submitName = 'submitAndContinue';
        } else {
            $submitName = 'submit';
        }

        $this->onPageSave($this, $submitName);
    }

    /**
     * Připraví možnosti obsahů stránky pro select.
     * @return string[]
     */
    private function prepareContentTypesOptions() : array
    {
        $options = [];
        foreach (Content::$types as $type) {
            $options[$type] = 'common.content.name.' . $type;
        }
        return $options;
    }
}
