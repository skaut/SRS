<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * Entita obsahu s informací o pořadateli.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="organizer_content")
 */
class OrganizerContent extends Content implements IContent
{
    protected $type = Content::ORGANIZER;

    /**
     * Pořadatel.
     * @ORM\Column(type="string", nullable=true)
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
    public function addContentForm(Form $form) : Form
    {
        parent::addContentForm($form);

        $formContainer = $form[$this->getContentFormName()];

        $formContainer->addText('organizer', 'admin.cms.pages_content_organizer')
            ->setDefaultValue($this->organizer);

        return $form;
    }

    /**
     * Zpracuje při uložení stránky část formuláře týkající se obsahu.
     * @param array $values
     */
    public function contentFormSucceeded(Form $form, array $values) : void
    {
        parent::contentFormSucceeded($form, $values);
        $values          = $values[$this->getContentFormName()];
        $this->organizer = $values['organizer'];
    }
}
