<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseForm;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\CapacitiesContent;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\ContentRepository;
use App\Model\CMS\Content\DocumentContent;
use App\Model\CMS\Content\ImageContent;
use App\Model\CMS\Content\UsersContent;
use App\Model\CMS\Document\TagRepository;
use App\Model\CMS\Page;
use App\Model\CMS\PageRepository;
use App\Services\FilesService;
use Nette\Application\UI;
use Nette\Application\UI\Form;


/**
 * Komponenta s formulářem pro úpravu obsahu stránky.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
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
     */
    public $onPageSave;
    
    /**
     * Událost při chybě ukládání stránky.
     */
    public $onPageSaveError;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var PageRepository */
    private $pageRepository;

    /** @var ContentRepository */
    private $contentRepository;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var TagRepository */
    private $tagRepository;

    /** @var FilesService */
    private $filesService;


    /**
     * PageForm constructor.
     * @param $id
     * @param $area
     * @param BaseForm $baseFormFactory
     * @param PageRepository $pageRepository
     * @param ContentRepository $contentRepository
     * @param RoleRepository $roleRepository
     * @param TagRepository $tagRepository
     * @param FilesService $filesService
     */
    public function __construct($id, $area, BaseForm $baseFormFactory, PageRepository $pageRepository,
                                ContentRepository $contentRepository, RoleRepository $roleRepository,
                                TagRepository $tagRepository, FilesService $filesService)
    {
        parent::__construct();

        $this->id = $id;
        $this->area = $area;

        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository = $pageRepository;
        $this->contentRepository = $contentRepository;
        $this->roleRepository = $roleRepository;
        $this->tagRepository = $tagRepository;
        $this->filesService = $filesService;

        $this->page = $this->pageRepository->findById($id);
    }

    /**
     * Vykreslí komponentu.
     */
    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/page_form.latte');

        $this->template->area = $this->area;
        $this->template->contents = $this->page->getContents($this->area);

        $this->template->render();
    }

    /**
     * Vytvoří komponentu.
     * @return Form
     * @throws \App\Model\Page\PageException
     */
    public function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addHidden('id')->setDefaultValue($this->page->getId());
        $form->addHidden('area')->setDefaultValue($this->area);
        $form->addSelect('type', 'admin.cms.pages_content_type', $this->prepareContentTypesOptions());

        foreach ($this->page->getContents($this->area) as $content) {
            switch (get_class($content)) {
                case CapacitiesContent::class:
                    $content->injectRoleRepository($this->roleRepository);
                    break;
                case DocumentContent::class:
                    $content->injectTagRepository($this->tagRepository);
                    break;
                case ImageContent::class:
                    $content->injectFilesService($this->filesService);
                    break;
                case UsersContent::class:
                    $content->injectRoleRepository($this->roleRepository);
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

        $form->onError[] = function (Form $form) {
            $this->onPageSaveError($this);
        };

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @param Form $form
     * @param \stdClass $values
     * @throws \App\Model\Page\PageException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function processForm(Form $form, \stdClass $values)
    {
        $page = $this->pageRepository->findById($values['id']);

        $area = $values['area'];
        $type = $values['type'];

        foreach ($page->getContents($area) as $content) {
            $formContainer = $values[$content->getContentFormName()];
            if ($formContainer['delete'])
                $this->contentRepository->remove($content);
            else
                $content->contentFormSucceeded($form, $values);
        }

        if ($form['submitAdd']->isSubmittedBy()) {
            $contentClass = '\\App\\Model\\CMS\\Content\\' . ucfirst($type) . 'Content';
            $content = new $contentClass($page, $area);
            $content->setHeading($form->getTranslator()->translate('common.content.default_heading.' . $type));
            $page->addContent($content);
        }

        $this->pageRepository->save($page);

        if ($form['submitAdd']->isSubmittedBy())
            $submitName = 'submitAdd';
        elseif ($form['submitMain']->isSubmittedBy())
            $submitName = 'submitMain';
        elseif ($form['submitSidebar']->isSubmittedBy())
            $submitName = 'submitSidebar';
        elseif ($form['submitAndContinue']->isSubmittedBy())
            $submitName = 'submitAndContinue';
        else
            $submitName = 'submit';

        $this->onPageSave($this, $submitName);
    }

    /**
     * Připraví možnosti obsahů stránky pro select.
     * @return array
     */
    private function prepareContentTypesOptions()
    {
        $options = [];
        foreach (Content::$types as $type)
            $options[$type] = 'common.content.name.' . $type;
        return $options;
    }
}
