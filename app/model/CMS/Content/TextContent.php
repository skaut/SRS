<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;


/**
 * @ORM\Entity
 * @ORM\Table(name="text_content")
 */
class TextContent extends Content implements IContent
{
    protected $type = Content::TEXT;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $text;


    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    public function addContentForm(Form $form)
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addTextArea('text', 'admin.cms.pages_content_text')
            ->setDefaultValue($this->text)
            ->setAttribute('class', 'tinymce');

        return $form;
    }

    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->text = $values['text'];
    }
}