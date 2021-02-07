<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Dto\NewsContentDto;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use stdClass;

use function assert;

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
    protected string $type = Content::NEWS;

    /**
     * Počet posledních novinek k zobrazení.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected ?int $count = null;

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function setCount(?int $count): void
    {
        $this->count = $count;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form): Form
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];
        assert($formContainer instanceof Container);
        $formContainer->addText('count', 'admin.cms.pages.content.form.news_count')
            ->setDefaultValue($this->count)
            ->setHtmlAttribute('data-toggle', 'tooltip')
            ->setHtmlAttribute('data-placement', 'bottom')
            ->setHtmlAttribute('title', $form->getTranslator()->translate('admin.cms.pages.content.form.news_count_note'))
            ->addCondition(Form::FILLED)->addRule(Form::INTEGER, 'admin.cms.pages.content.form.news_count_format');

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, stdClass $values): void
    {
        parent::contentFormSucceeded($form, $values);
        $formName    = $this->getContentFormName();
        $values      = $values->$formName;
        $this->count = $values->count !== '' ? $values->count : null;
    }

    public function convertToDto(): ContentDto
    {
        return new NewsContentDto($this->getComponentName(), $this->heading, $this->count);
    }
}
