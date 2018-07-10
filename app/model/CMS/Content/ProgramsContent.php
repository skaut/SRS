<?php
declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita obsahu s výběrem programů.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="programs_content")
 */
class ProgramsContent extends Content implements IContent
{
    protected $type = Content::PROGRAMS;
}
