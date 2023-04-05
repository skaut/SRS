<?php

declare(strict_types=1);

namespace App\Model\Cms;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s přihláškou oddílu.
 */
#[ORM\Entity]
#[ORM\Table(name: 'troop_application_content')]
class TroopApplicationContent extends Content implements IContent
{
    protected string $type = Content::TROOP_APPLICATION;
}
