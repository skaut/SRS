<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu se seznamem lektorů.
 *
 * @ORM\Entity
 * @ORM\Table(name="lectors_content")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class LectorsContent extends Content implements IContent
{
    protected string $type = Content::LECTORS;
}
