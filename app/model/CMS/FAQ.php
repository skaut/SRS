<?php

namespace App\Model\CMS;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class FAQ
{
    use \Kdyby\Doctrine\Entities\Attributes\Identifier;

    /** @ORM\Column(type="text") */
    protected $question;

    /** @ORM\ManyToOne(targetEntity="\SRS\model\User") */
    protected $author;

    /** @ORM\Column(type="text", nullable=true) */
    protected $answer;

    /** @ORM\Column(type="integer") */
    protected $position = 0;

    /** @ORM\Column(type="boolean") */
    protected $public = false;
}