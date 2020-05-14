<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use stdClass;

/**
 * Entita obsahu s textem.
 *
 * @ORM\Entity
 * @ORM\Table(name="text_content")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class TextContent extends Content implements IContent
{
    protected string $type = Content::TEXT;

    /**
     * Text.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $text;

    public function getText() : ?string
    {
        return $this->text;
    }

    public function setText(?string $text) : void
    {
        $this->text = $text;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form) : Form
    {
        parent::addContentForm($form);

        /** @var Container $formContainer */
        $formContainer = $form[$this->getContentFormName()];
        $formContainer->addTextArea('text', 'admin.cms.pages_content_text')
            ->setDefaultValue($this->text)
            ->setHtmlAttribute('class', 'tinymce');

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
        $this->text = $values->text;
    }

    public function convertToDto() : ContentDto
    {
        return new TextContentDto($this->getComponentName(), $this->heading, $this->text);
    }
}
