<?php

namespace App\AdminModule\CMSModule\Forms;

use App\AdminModule\Forms\BaseFormFactory;
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
use App\Model\CMS\PageRepository;
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

    public function __construct(BaseFormFactory $baseFormFactory, PageRepository $pageRepository, ContentRepository $contentRepository)
    {
        $this->baseFormFactory = $baseFormFactory;
        $this->pageRepository = $pageRepository;
        $this->contentRepository = $contentRepository;
    }

    public function create($id, $area)
    {
        $form = $this->baseFormFactory->create();

        $page = $this->pageRepository->findPageById($id);

        $form->addHidden('id')->setDefaultValue($page->getId());
        $form->addHidden('area')->setDefaultValue($area);
        $form->addSelect('type', 'admin.cms.pages_content_type', $this->prepareContentTypesOptions($form->getTranslator()))
            ->setPrompt('admin.common.choose');
        $form->addSubmit('submitAdd', 'admin.common.add');

        foreach ($page->getContents($area) as $content) {
            $form = $content->addContentForm($form);
        }

        $form->addSubmit('submit', 'admin.common.save');
        $form->addSubmit('submitAndContinue', 'admin.common.save_and_continue');

        $form->onSuccess[] = [$this, 'formSucceeded'];

        return $form;
    }

    public function formSucceeded(Form $form, \stdClass $values) {
        $page = $this->pageRepository->findPageById($values['id']);

        foreach ($page->getContents($values['area']) as $content) {
            $formContainer = $values[$content->getContentFormName()];
            if ($formContainer['delete'])
                $this->contentRepository->removeContent($content->getId());
            else
                $content->contentFormSucceeded($form, $values);
        }

        $type = $values['type'];
        if ($type != null) {
            $contentClass = '\\App\\Model\\CMS\\Content\\' . ucfirst($type) . 'Content';
            $content = new $contentClass($page, $values['area']);
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
