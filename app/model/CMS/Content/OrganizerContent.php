<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use App\AdminModule\Forms\BaseForm;
use Doctrine\ORM\Mapping as ORM;
use stdClass;

/**
 * Entita obsahu s informací o pořadateli.
 *
 * @ORM\Entity
 * @ORM\Table(name="organizer_content")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class OrganizerContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::ORGANIZER;

    /**
     * Pořadatel.
     *
     * @ORM\Column(type="string", nullable=true)
     *
     * @var string
     */
    protected $organizer;

    public function getOrganizer() : ?string
    {
        return $this->organizer;
    }

    public function setOrganizer(?string $organizer) : void
    {
        $this->organizer = $organizer;
    }

    /**
     * Přidá do formuláře pro editaci stránky formulář pro úpravu obsahu.
     */
    public function addContentForm(BaseForm $form) : BaseForm
    {
        parent::addContentForm($form);

        $formName      = $this->getContentFormName();
        $formContainer = $form->$formName;

        $formContainer->addText('organizer', 'admin.cms.pages_content_organizer')
            ->setDefaultValue($this->organizer);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     */
    public function contentFormSucceeded(BaseForm $form, stdClass $values) : void
    {
        parent::contentFormSucceeded($form, $values);
        $formName        = $this->getContentFormName();
        $values          = $values->$formName;
        $this->organizer = $values->organizer;
    }

    public function convertToDto() : ContentDto
    {
        return new OrganizerContentDto($this->getComponentName(), $this->heading, $this->organizer);
    }
}
