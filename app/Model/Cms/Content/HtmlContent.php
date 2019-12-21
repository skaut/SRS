<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use App\AdminModule\Forms\BaseForm;
use Doctrine\ORM\Mapping as ORM;
use stdClass;

/**
 * Entita obsahu s HTML.
 *
 * @ORM\Entity
 * @ORM\Table(name="html_content")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class HtmlContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::HTML;

    /**
     * Text.
     *
     * @ORM\Column(type="text", nullable=true)
     *
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

        $formName      = $this->getContentFormName();
        $formContainer = $form->$formName;

        $formContainer->addTextArea('text', 'admin.cms.pages_content_html')
            ->setDefaultValue($this->text)
            ->setAttribute('rows', 5);

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
        return new HtmlContentDto($this->getComponentName(), $this->heading, $this->text);
    }
}
