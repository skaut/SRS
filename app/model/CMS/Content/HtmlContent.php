<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;

/**
 * Entita obsahu s HTML.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @ORM\Entity
 * @ORM\Table(name="html_content")
 */
class HtmlContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::HTML;

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
    public function addContentForm(Form $form) : Form
    {
        parent::addContentForm($form);

        /** @var Container $formContainer */
        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addTextArea('text', 'admin.cms.pages_content_html')
            ->setDefaultValue($this->text)
            ->setAttribute('rows', 5);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, \stdClass $values) : void
    {
        parent::contentFormSucceeded($form, $values);
        $values     = $values[$this->getContentFormName()];
        $this->text = $values['text'];
    }

    public function convertToDTO() : ContentDTO
    {
        return new HtmlContentDTO($this->getComponentName(), $this->heading, $this->text);
    }
}
