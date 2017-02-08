<?php

namespace App\Model\CMS\Content;


use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="news_content")
 */
class NewsContent extends Content implements IContent
{
    protected $type = Content::NEWS;

    /**
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

    public function addContentForm(Form $form)
    {
        parent::addContentForm($form);
        $formContainer = $form[$this->getContentFormName()];
        $formContainer->addText('count', 'admin.cms.pages_content_news_count')
            ->setDefaultValue($this->count)
            ->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'admin.cms.pages_content_news_count_format');
        return $form;
    }

    public function contentFormSucceeded(Form $form, \stdClass $values)
    {
        parent::contentFormSucceeded($form, $values);
        $values = $values[$this->getContentFormName()];
        $this->count = $values['count'];
    }
}