<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 */
class BlockBoxContent extends Content implements IContent
{
    protected $content = Content::BLOCK_BOX;
}