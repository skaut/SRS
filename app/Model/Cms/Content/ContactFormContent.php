<?php

declare(strict_types=1);

namespace App\Model\Cms\Content;

use Doctrine\ORM\Mapping as ORM;

/**
 * Entita obsahu s kontaktním formulářem.
 *
 * @ORM\Entity
 * @ORM\Table(name="contact_form_content")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class ContactFormContent extends Content implements IContent
{
    protected string $type = Content::CONTACT_FORM;
}
