<?php

namespace App\Model\CMS\Content;

use App\Model\CMS\Document\CategoryDocumentRepository;
use App\Model\CMS\Page;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;


/**
 * Entita obsahu s dokumenty.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @ORM\Entity
 * @ORM\Table(name="document_content")
 */
class DocumentContent extends Content implements IContent
{
    protected $type = Content::DOCUMENT;

    /**
     * Kategorie dokumentů, které se zobrazí.
     * @ORM\ManyToMany(targetEntity="\App\Model\CMS\Document\CategoryDocument")
     * @var Collection
     */
    protected $categoriesDocument;

    /** @var CategoryDocumentRepository */
    private $categoryDocumentRepository;


    /**
     * DocumentContent constructor.
     * @param Page $page
     * @param $area
     */
    public function __construct(Page $page, $area)
    {
        parent::__construct($page, $area);
        $this->documentCategories = new ArrayCollection();
    }

    /**
     * @param CategoryDocumentRepository $categoryDocumentRepository
     */
    public function injectCategoryDocumentRepository(CategoryDocumentRepository $categoryDocumentRepository)
    {
        $this->categoryDocumentRepository = $categoryDocumentRepository;
    }

    /**
     * @return Collection
     */
    public function getDocumentCategories()
    {
        return $this->documentCategories;
    }

    /**
     * @param Collection $documentCategories
     */
    public function setDocumentCategories($documentCategories)
    {
        $this->documentCategories = $documentCategories;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     * @param Form $form
     * @return Form
     */
    public function addContentForm(Form $form)
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addMultiSelect('documentCategories', 'admin.cms.pages_content_categories', $this->documentCategories->getDocumentCategoriesOptions())
            ->setDefaultValue($this->documentCategories->findDocumentCategories($this->documentCategories));

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param Form $form
     * @param \stdClass $values
     */
    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->documentCategories = $this->categoryDocumentRepository->findTagsByIds($values['documentCategories']);
    }
}
