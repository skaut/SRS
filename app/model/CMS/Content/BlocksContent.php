<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="blocks_content")
 */
class BlocksContent extends Content implements IContent
{
    protected $type = Content::BLOCKS;
}