<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;


/**
 * Entita obsahu s popisem cesty.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="place_content")
 */
class PlaceContent extends Content implements IContent
{
    protected $type = Content::PLACE;
}