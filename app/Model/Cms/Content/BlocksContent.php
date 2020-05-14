<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s přehledem bloků.
 *
 * @ORM\Entity
 * @ORM\Table(name="blocks_content")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlocksContent extends Content implements IContent
{
    protected string $type = Content::BLOCKS;
}
