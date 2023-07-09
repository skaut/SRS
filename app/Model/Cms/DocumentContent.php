<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Dto\DocumentContentDto;
use App\Model\Cms\Exceptions\PageException;
use App\Model\Cms\Repositories\TagRepository;
use App\Utils\Helpers;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use stdClass;

use function assert;

/**
 * Entita obsahu s dokumenty.
 */
#[ORM\Entity]
#[ORM\Table(name: 'document_content')]
class DocumentContent extends Content implements IContent
{
    protected string $type = Content::DOCUMENT;

    /**
     * Tagy dokumentů, které se zobrazí.
     *
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    protected Collection $tags;

    private TagRepository $tagRepository;

    /** @throws PageException */
    public function __construct(Page $page, string $area)
    {
        parent::__construct($page, $area);

        $this->tags = new ArrayCollection();
    }

    public function injectTagRepository(TagRepository $tagRepository): void
    {
        $this->tagRepository = $tagRepository;
    }

    /** @return Collection<int, Tag> */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /** @param Collection<int, Tag> $tags */
    public function setTags(Collection $tags): void
    {
        $this->tags->clear();
        foreach ($tags as $tag) {
            $this->tags->add($tag);
        }
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form): Form
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];
        assert($formContainer instanceof Container);
        $formContainer->addMultiSelect('tags', 'admin.cms.pages.content.form.tags', $this->tagRepository->getTagsOptions())
            ->setDefaultValue($this->tagRepository->findTagsIds($this->tags));

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, stdClass $values): void
    {
        parent::contentFormSucceeded($form, $values);

        $formName   = $this->getContentFormName();
        $values     = $values->$formName;
        $this->tags = $this->tagRepository->findTagsByIds($values->tags);
    }

    public function convertToDto(): ContentDto
    {
        return new DocumentContentDto($this->getComponentName(), $this->heading, Helpers::getIds($this->tags));
    }
}
