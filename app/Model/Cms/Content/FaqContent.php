<?php

declare(strict_types=1);

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s FAQ.
 *
 * @ORM\Entity
 * @ORM\Table(name="faq_content")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class FaqContent extends Content implements IContent
{
    /** @var string */
    protected $type = Content::FAQ;
}
