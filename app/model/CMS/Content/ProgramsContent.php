<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s výběrem programů.
 *
 * @ORM\Entity
 * @ORM\Table(name="programs_content")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ProgramsContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::PROGRAMS;
}
