<?php

namespace App\Model\CMS\Content;

use App\Model\CMS\Document\TagRepository;
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
 * @ORM\Entity
 * @ORM\Table(name="document_content")
 */
class DocumentContent extends Content implements IContent
{
    protected $type = Content::DOCUMENT;

    /**
     * Tagy dokumentů, které se zobrazí.
     * @ORM\ManyToMany(targetEntity="\App\Model\CMS\Document\Tag")
     * @var Collection
     */
    protected $tags;

    /** @var TagRepository */
    private $tagRepository;


    /**
     * DocumentContent constructor.
     * @param Page $page
     * @param $area
     * @throws \App\Model\Page\PageException
     */
    public function __construct(Page $page, $area)
    {
        parent::__construct($page, $area);
        $this->tags = new ArrayCollection();
    }

    /**
     * @param TagRepository $tagRepository
     */
    public function injectTagRepository(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * @return Collection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Collection $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
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

        $formContainer->addMultiSelect('tags', 'admin.cms.pages_content_tags', $this->tagRepository->getTagsOptions())
            ->setDefaultValue($this->tagRepository->findTagsIds($this->tags));

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
        $this->tags = $this->tagRepository->findTagsByIds($values['tags']);
    }
}
