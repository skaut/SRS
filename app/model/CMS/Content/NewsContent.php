<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;


/**
 * Entita obsahu s aktualitami.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="news_content")
 */
class NewsContent extends Content implements IContent
{
    protected $type = Content::NEWS;

    /**
     * Počet posledních novinek k zobrazení.
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $count;


    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount($count)
    {
        $this->count = $count;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     * @param Form $form
     * @return Form
     */
    public function addContentForm(Form $form)
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addText('count', 'admin.cms.pages_content_news_count')
            ->setDefaultValue($this->count)
            ->setAttribute('data-toggle', 'tooltip')
            ->setAttribute('title', $form->getTranslator()->translate('admin.cms.pages_content_news_count_note'))
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.cms.pages_content_news_count_format');

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param Form $form
     * @param \stdClass $values
     */
    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->count = $values['count'] !== '' ? $values['count'] : NULL;
    }
}
