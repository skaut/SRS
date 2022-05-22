<?php

declare(strict_types=1);

namespace App\Model\Cms;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s výběrem programů.
 */
#[ORM\Entity]
#[ORM\Table(name: 'programs_content')]
class ProgramsContent extends Content implements IContent
{
    protected string $type = Content::PROGRAMS;
}
