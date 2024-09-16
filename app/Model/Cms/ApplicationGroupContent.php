<?php

declare(strict_types=1);

namespace App\Model\Cms;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s přihláškou.
 */
#[ORM\Entity]
#[ORM\Table(name: 'application_group_content')]
class ApplicationGroupContent extends Content implements IContent
{
    protected string $type = Content::APPLICATION_GROUP;
}
