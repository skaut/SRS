<?php

declare(strict_types=1);

namespace App\Model\Cms;

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
    protected string $type = Content::PROGRAMS;
}
