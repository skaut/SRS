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
    /** @var string */
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
}
