<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Dto\TextContentDto;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use stdClass;

use function assert;

/**
 * Entita obsahu s textem.
 *
 * @ORM\Entity
 * @ORM\Table(name="text_content")
 */
class TextContent extends Content implements IContent
{
    protected string $type = Content::TEXT;

    /**
     * Text.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $text = null;

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form): Form
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];
        assert($formContainer instanceof Container);
        $formContainer->addTextArea('text', 'admin.cms.pages.content.form.text')
            ->setDefaultValue($this->text)
            ->setHtmlAttribute('class', 'tinymce');

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
        $this->text = $values->text;
    }

    public function convertToDto(): ContentDto
    {
        return new TextContentDto($this->getComponentName(), $this->heading, $this->text);
    }
}
