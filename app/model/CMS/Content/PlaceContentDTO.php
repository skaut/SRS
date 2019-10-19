<?php

declare(strict_types=1);

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
    /** @var string */
    protected $type = Content::PLACE;
}
