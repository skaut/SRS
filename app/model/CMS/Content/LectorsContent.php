<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu se seznamem lektorů.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="lectors_content")
 */
class LectorsContent extends Content implements IContent
{
    protected $type = Content::LECTORS;
}
