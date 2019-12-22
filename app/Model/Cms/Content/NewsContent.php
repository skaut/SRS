<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use stdClass;

/**
 * Entita obsahu s aktualitami.
 *
 * @ORM\Entity
 * @ORM\Table(name="news_content")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class NewsContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::NEWS;

    /**
     * Počet posledních novinek k zobrazení.
     *
     * @ORM\Column(type="integer", nullable=true)
     *
     * @var int
     */
    protected $count;

    public function getCount() : ?int
    {
        return $this->count;
    }

    public function setCount(?int $count) : void
    {
        $this->count = $count;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form) : Form
    {
        parent::addContentForm($form);

        $formName      = $this->getContentFormName();
        $formContainer = $form->$formName;

        $formContainer->addText('count', 'admin.cms.pages_content_news_count')
            ->setDefaultValue($this->count)
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.cms.pages_content_news_count_note'))
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.cms.pages_content_news_count_format');

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, stdClass $values) : void
    {
        parent::contentFormSucceeded($form, $values);
        $formName    = $this->getContentFormName();
        $values      = $values->$formName;
        $this->count = $values->count !== '' ? $values->count : null;
    }

    public function convertToDto() : ContentDto
    {
        return new NewsContentDto($this->getComponentName(), $this->heading, $this->count);
    }
}
