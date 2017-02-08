<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="programs_content")
 */
class ProgramsContent extends Content implements IContent
{
    protected $type = Content::PROGRAMS;
}