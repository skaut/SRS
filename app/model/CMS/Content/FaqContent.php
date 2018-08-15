<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s FAQ.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="faq_content")
 */
class FaqContent extends Content implements IContent
{
    protected $type = Content::FAQ;
}
