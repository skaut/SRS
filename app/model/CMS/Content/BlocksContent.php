<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s přehledem bloků.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="blocks_content")
 */
class BlocksContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::BLOCKS;
}
