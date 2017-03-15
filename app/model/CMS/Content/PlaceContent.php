<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;


/**
 * @ORM\Entity
 * @ORM\Table(name="place_content")
 */
class PlaceContent extends Content implements IContent
{
    protected $type = Content::PLACE;
}