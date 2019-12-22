<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use App\Model\Cms\Document\Tag;
use App\Model\Cms\Document\TagRepository;
use App\Model\Cms\Page;
use App\Model\Cms\PageException;
use App\Utils\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Entita obsahu s dokumenty.
 *
 * @ORM\Entity
 * @ORM\Table(name="document_content")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class DocumentContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::DOCUMENT;

    /**
     * Tagy dokumentů, které se zobrazí.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Cms\Document\Tag")
     *
     * @var Collection|Tag[]
     */
    protected $tags;

    /** @var TagRepository */
    private $tagRepository;

    /**
     * @throws PageException
     */
    public function __construct(Page $page, string $area)
    {
        parent::__construct($page, $area);
        $this->tags = new ArrayCollection();
    }

    public function injectTagRepository(TagRepository $tagRepository) : void
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags() : Collection
    {
        return $this->tags;
    }

    /**
     * @param Collection|Tag[] $tags
     */
    public function setTags(Collection $tags) : void
    {
        $this->tags->clear();
        foreach ($tags as $tag) {
            $this->tags->add($tag);
        }
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form) : Form
    {
        parent::addContentForm($form);

        $formName      = $this->getContentFormName();
        $formContainer = $form->$formName;

        $formContainer->addMultiSelect('tags', 'admin.cms.pages_content_tags', $this->tagRepository->getTagsOptions())
            ->setDefaultValue($this->tagRepository->findTagsIds($this->tags));

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, stdClass $values) : void
    {
        parent::contentFormSucceeded($form, $values);
        $formName   = $this->getContentFormName();
        $values     = $values->$formName;
        $this->tags = $this->tagRepository->findTagsByIds($values->tags);
    }

    public function convertToDto() : ContentDto
    {
        return new DocumentContentDto($this->getComponentName(), $this->heading, Helpers::getIds($this->tags));
    }
}
