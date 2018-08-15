<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * Entita obsahu s HTML.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="html_content")
 */
class HtmlContent extends Content implements IContent
{
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

        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addTextArea('text', 'admin.cms.pages_content_html')
            ->setDefaultValue($this->text)
            ->setAttribute('rows', 5);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param array $values
     */
    public function contentFormSucceeded(Form $form, array $values) : void
    {
        parent::contentFormSucceeded($form, $values);
        $values     = $values[$this->getContentFormName()];
        $this->text = $values['text'];
    }
}
