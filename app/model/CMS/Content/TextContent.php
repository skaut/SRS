<?php
declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;


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
    protected $type = Content::TEXT;

    /**
     * Text.
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $text;


    /**
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(?string $text): void
    {
        $this->text = $text;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     * @param Form $form
     * @return Form
     */
    public function addContentForm(Form $form): Form
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addTextArea('text', 'admin.cms.pages_content_text')
            ->setDefaultValue($this->text)
            ->setAttribute('class', 'tinymce');

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param Form $form
     * @param array $values
     */
    public function contentFormSucceeded(Form $form, array $values): void
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->text = $values['text'];
    }
}
