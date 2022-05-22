<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Dto\HtmlContentDto;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use stdClass;

use function assert;

/**
 * Entita obsahu s HTML.
 */
#[ORM\Entity]
#[ORM\Table(name: 'html_content')]
class HtmlContent extends Content implements IContent
{
    protected string $type = Content::HTML;

    /**
     * Text.
     */
    #[ORM\Column(type: 'text', nullable: true)]
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
        $formContainer->addTextArea('text', 'admin.cms.pages.content.form.html')
            ->setDefaultValue($this->text)
            ->setHtmlAttribute('rows', 5);

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
        return new HtmlContentDto($this->getComponentName(), $this->heading, $this->text);
    }
}
