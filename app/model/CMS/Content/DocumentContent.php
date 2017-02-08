<?php

namespace App\Model\CMS\Content;


use App\Model\CMS\Document\Tag;
use App\Model\CMS\Document\TagRepository;
use App\Model\CMS\Page;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="document_content")
 */
class DocumentContent extends Content implements IContent
{
    protected $type = Content::DOCUMENT;

    /**
     * @ORM\ManyToMany(targetEntity="\App\Model\CMS\Document\Tag")
     * @var ArrayCollection
     */
    protected $tags;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    public function __construct(Page $page, $area)
    {
        parent::__construct($page, $area);
        $this->tags = new ArrayCollection();
    }

    /**
     * @param TagRepository $tagRepository
     */
    public function injectRoleRepository(TagRepository $tagRepository) {
        $this->tagRepository = $tagRepository;
    }

    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param ArrayCollection $tags
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
    }

    public function addContentForm(Form $form)
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];

        $tagsIds = array_map(function($o) { return $o->getId(); }, $this->tags->toArray());
        $formContainer->addMultiSelect('tags', 'admin.cms.pages_content_tags', $this->tagRepository->getTagsOptions())
            ->setDefaultValue($tagsIds);

        return $form;
    }

    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->tags = $this->tagRepository->findTagsByIds($values['tags']);
    }
}