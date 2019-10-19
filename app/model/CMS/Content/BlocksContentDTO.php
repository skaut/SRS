<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu s přehledem bloků.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class BlocksContentDTO extends Content
{
    /** @var string */
    protected $type = Content::BLOCKS;
}
