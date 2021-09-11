<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Cms\Dto\ContentDto;
use App\Model\Cms\Dto\OrganizerContentDto;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;
use Nette\Forms\Container;
use stdClass;

use function assert;

/**
 * Entita obsahu s informací o pořadateli.
 *
 * @ORM\Entity
 * @ORM\Table(name="organizer_content")
 */
class OrganizerContent extends Content implements IContent
{
    protected string $type = Content::ORGANIZER;

    /**
     * Pořadatel.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected ?string $organizer = null;

    public function getOrganizer(): ?string
    {
        return $this->organizer;
    }

    public function setOrganizer(?string $organizer): void
    {
        $this->organizer = $organizer;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(Form $form): Form
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];
        assert($formContainer instanceof Container);
        $formContainer->addText('organizer', 'admin.cms.pages.content.form.organizer')
            ->setDefaultValue($this->organizer);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(Form $form, stdClass $values): void
    {
        parent::contentFormSucceeded($form, $values);
        $formName        = $this->getContentFormName();
        $values          = $values->$formName;
        $this->organizer = $values->organizer;
    }

    public function convertToDto(): ContentDto
    {
        return new OrganizerContentDto($this->getComponentName(), $this->heading, $this->organizer);
    }
}
