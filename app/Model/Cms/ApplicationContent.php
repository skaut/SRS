<?php

declare(strict_types=1);

namespace App\Model\Cms;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s přihláškou.
 *
 * @ORM\Entity
 * @ORM\Table(name="application_content")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ApplicationContent extends Content implements IContent
{
    protected string $type = Content::APPLICATION;
}
