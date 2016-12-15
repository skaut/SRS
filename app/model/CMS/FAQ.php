<?php

namespace App\Model\CMS;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="faq")
 */
class FAQ
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="text") */
    protected $question;

    /** @ORM\ManyToOne(targetEntity="\App\Model\User\User") */
    protected $author;

    /** @ORM\Column(type="text", nullable=true) */
    protected $answer;

    /** @ORM\Column(type="integer") */
    protected $position = 0;

    /** @ORM\Column(type="boolean") */
    protected $public = false;
}