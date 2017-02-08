<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
use App\Model\ACL\RoleRepository;
use App\Model\CMS\Content\ApplicationContent;
use App\Model\CMS\Content\BlocksContent;
use App\Model\CMS\Content\CapacitiesContent;
use App\Model\CMS\Content\Content;
use App\Model\CMS\Content\ContentRepository;
use App\Model\CMS\Content\DocumentContent;
use App\Model\CMS\Content\FaqContent;
use App\Model\CMS\Content\HtmlContent;
use App\Model\CMS\Content\ImageContent;
use App\Model\CMS\Content\NewsContent;
use App\Model\CMS\Content\ProgramsContent;
use App\Model\CMS\Content\TextContent;
use App\Model\CMS\Content\UsersContent;
use App\Model\CMS\Document\TagRepository;
use App\Model\CMS\PageRepository;
use App\Services\FilesService;
use Nette\Application\UI\Form;

class PageFormFactory
{
    /**
     * @var BaseFormFactory
     */
    private $baseFormFactory;

    /**
     * @var PageRepository
     */
    private $pageRepository;

    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var RoleRepository
     */
    private $roleRepository;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    /**
     * @var FilesService
     */
    private $filesService;

    public function __construct(BaseFormFactory $baseFormFactory, PageRepository $pageRepository,
                                ContentRepository $contentRepository, RoleRepository $roleRepository,
                                TagRepository $tagRepository, FilesService $filesService)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository = $pageRepository;
        $this->contentRepository = $contentRepository;
        $this->roleRepository = $roleRepository;
        $this->tagRepository = $tagRepository;
        $this->filesService = $filesService;
    }

    public function create($id, $area)
    {
        $form = $this->baseFormFactory->create();

        $page = $this->pageRepository->findPageById($id);

        $form->addHidden('id')->setDefaultValue($page->getId());
        $form->addHidden('area')->setDefaultValue($area);
        $form->addSelect('type', 'admin.cms.pages_content_type', $this->prepareContentTypesOptions($form->getTranslator()));

        foreach ($page->getContents($area) as $content) {
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

        $form->onSuccess[] = [$this, 'formSucceeded'];

        return $form;
    }

    public function formSucceeded(Form $form, \stdClass $values) {
        $page = $this->pageRepository->findPageById($values['id']);

        $area = $values['area'];
        $type = $values['type'];

        foreach ($page->getContents($area) as $content) {
            $formContainer = $values[$content->getContentFormName()];
            if ($formContainer['delete'])
                $this->contentRepository->removeContent($content->getId());
            else
                $content->contentFormSucceeded($form, $values);
        }

        if ($form['submitAdd']->isSubmittedBy()) {
            $contentClass = '\\App\\Model\\CMS\\Content\\' . ucfirst($type) . 'Content';
            $content = new $contentClass($page, $area);
            $content->setHeading($form->getTranslator()->translate('common.content.default_heading.' . $type));
            $page->addContent($content);
        }

        $this->pageRepository->getEntityManager()->flush();
    }

    private function prepareContentTypesOptions($translator) {
        $options = [];
        foreach (Content::$types as $type)
            $options[$type] = $translator->translate('common.content.name.' . $type);
        return $options;
    }
}
