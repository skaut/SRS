<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use App\AdminModule\Forms\BaseForm;
use Doctrine\ORM\Mapping as ORM;
use stdClass;

/**
 * Entita obsahu s textem.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="text_content")
 */
class TextContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::TEXT;

    /**
     * Text.
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $text;


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
    public function addContentForm(BaseForm $form) : BaseForm
    {
        parent::addContentForm($form);

        $formName = $this->getContentFormName();
        $formContainer = $form->$formName;

        $formContainer->addTextArea('text', 'admin.cms.pages_content_text')
            ->setDefaultValue($this->text)
            ->setAttribute('class', 'tinymce');

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(BaseForm $form, stdClass $values) : void
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
