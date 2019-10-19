<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

/**
 * DTO obsahu s přihláškou.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationContentDTO extends Content
{
    /** @var string */
    protected $type = Content::APPLICATION;
}
