<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 */
class ProgramBoxContent extends Content implements IContent
{
    protected $content = Content::PROGRAM_BOX;
}