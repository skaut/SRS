<?php

declare(strict_types=1);

namespace App\Model\Cms;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s popisem cesty.
 *
 * @ORM\Entity
 * @ORM\Table(name="place_content")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class PlaceContent extends Content implements IContent
{
    protected string $type = Content::PLACE;
}
